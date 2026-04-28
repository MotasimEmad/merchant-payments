<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentIntentStatus;
use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\PaymentIntent;
use App\Models\User;
use App\Services\Payments\CustomerCheckoutService;
use App\Services\Payments\PlatformApplicationFee;
use App\Services\Payments\WalletOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PaymentIntentController extends Controller
{
    public function __construct(
        private readonly WalletOperationService $wallets,
        private readonly CustomerCheckoutService $customerCheckout,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'merchant_id' => 'required|integer|exists:merchants,id',
            'payer_user_id' => 'required|integer|exists:users,id',
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'description' => 'nullable|string|max:500',
            'idempotency' => 'nullable|string|max:120',
        ]);
        $merchant = Merchant::query()->findOrFail($data['merchant_id']);
        if ((int) $merchant->user_id !== (int) $user->id) {
            return response()->json(['message' => 'This merchant is not owned by the authenticated user.'], 403);
        }
        if ($response = $this->responseIfIdempotent($data['idempotency'] ?? null)) {
            return $response;
        }
        $idempotency = $data['idempotency'] ?? (string) Str::uuid();
        $gross = (int) $data['amount_minor'];
        $intent = PaymentIntent::query()->create([
            'public_id' => (string) Str::uuid(),
            'merchant_id' => $merchant->id,
            'payer_user_id' => $data['payer_user_id'],
            'amount_minor' => $gross,
            'application_fee_minor' => PlatformApplicationFee::minorFromGross($gross),
            'currency' => strtoupper($data['currency']),
            'status' => PaymentIntentStatus::RequiresPayment,
            'description' => $data['description'] ?? null,
            'idempotency' => $idempotency,
        ]);

        return response()->json($this->intentPayload($intent->fresh()), 201);
    }

    /**
     * Customer checkout: the authenticated user is the payer. Use either
     * service_public_id (price from catalog) or merchant_id + amount_minor + currency (custom).
     * A 5% platform application fee is computed from the gross amount.
     */
    public function storeAsPayer(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'service_public_id' => 'nullable|uuid|exists:merchant_services,public_id',
            'merchant_id' => 'required_without:service_public_id|integer|exists:merchants,id',
            'amount_minor' => 'required_without:service_public_id|integer|min:1',
            'currency' => 'required_without:service_public_id|string|size:3',
            'description' => 'nullable|string|max:500',
            'idempotency' => 'nullable|string|max:120',
        ]);
        if ($response = $this->responseIfIdempotent($data['idempotency'] ?? null)) {
            return $response;
        }
        $data['idempotency'] = $data['idempotency'] ?? (string) Str::uuid();
        $intent = $this->customerCheckout->createIntentForPayer($user, $data);

        return response()->json($this->intentPayload($intent->fresh(['ledgerBatch', 'merchantService'])), 201);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $user = $this->user($request);
        $intent = PaymentIntent::query()->where('public_id', $publicId)->firstOrFail();
        $this->authorizeIntentView($user, $intent);

        return response()->json($this->intentPayload($intent->loadMissing(['ledgerBatch', 'merchantService'])));
    }

    public function confirm(Request $request, string $publicId): JsonResponse
    {
        $user = $this->user($request);
        $intent = PaymentIntent::query()->where('public_id', $publicId)->firstOrFail();
        if ((int) $intent->payer_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Only the designated payer can confirm.'], 403);
        }
        $data = $request->validate([
            'idempotency' => 'required|string|max:120',
        ]);
        try {
            $intent = $this->wallets->capturePaymentIntent(
                $intent,
                "pi_confirm_{$publicId}_".$data['idempotency'],
                'api',
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($this->intentPayload($intent->loadMissing(['ledgerBatch', 'merchantService'])));
    }

    public function refund(Request $request, string $publicId): JsonResponse
    {
        $user = $this->user($request);
        $intent = PaymentIntent::query()->where('public_id', $publicId)->firstOrFail();
        $merchant = $intent->merchant;
        if (! $merchant || (int) $merchant->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Only the merchant can refund.'], 403);
        }
        $data = $request->validate([
            'amount_minor' => 'nullable|integer|min:1',
            'idempotency' => 'required|string|max:120',
        ]);
        $amount = (int) ($data['amount_minor'] ?? $intent->amount_minor);
        try {
            $batch = $this->wallets->refundPaymentIntent(
                $intent,
                $amount,
                "refund_{$publicId}_".$data['idempotency'],
                'api',
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $intent->refresh();

        return response()->json(array_merge(
            $this->intentPayload($intent),
            ['refund_batch_ref' => $batch->ref]
        ));
    }

    private function user(Request $request): User
    {
        $u = $request->user();
        if (! $u instanceof User) {
            abort(401);
        }

        return $u;
    }

    private function responseIfIdempotent(?string $idempotency): ?JsonResponse
    {
        if (! $idempotency) {
            return null;
        }
        $existing = PaymentIntent::query()->where('idempotency', $idempotency)->first();
        if (! $existing) {
            return null;
        }
        $existing->loadMissing(['ledgerBatch', 'merchantService']);

        return response()->json($this->intentPayload($existing), 200);
    }

    private function authorizeIntentView(User $user, PaymentIntent $intent): void
    {
        $m = $intent->merchant;
        if ((int) $intent->payer_user_id === (int) $user->id) {
            return;
        }
        if ($m && (int) $m->user_id === (int) $user->id) {
            return;
        }
        abort(403);
    }

    /**
     * @return array<string, mixed>
     */
    private function intentPayload(PaymentIntent $intent): array
    {
        $intent->loadMissing(['ledgerBatch', 'merchantService']);
        $status = $intent->status instanceof PaymentIntentStatus
            ? $intent->status->value
            : (string) $intent->status;

        $service = $intent->merchantService;

        return [
            'public_id' => $intent->public_id,
            'merchant_id' => $intent->merchant_id,
            'merchant_service_id' => $intent->merchant_service_id,
            'payer_user_id' => $intent->payer_user_id,
            'amount_minor' => $intent->amount_minor,
            'application_fee_minor' => $intent->application_fee_minor,
            'currency' => $intent->currency,
            'status' => $status,
            'description' => $intent->description,
            'batch_ref' => $intent->ledgerBatch?->ref,
            'service' => $service
                ? [
                    'public_id' => $service->public_id,
                    'name' => $service->name,
                ]
                : null,
        ];
    }
}
