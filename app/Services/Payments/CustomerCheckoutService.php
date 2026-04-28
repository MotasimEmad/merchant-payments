<?php

namespace App\Services\Payments;

use App\Enums\PaymentIntentStatus;
use App\Models\Merchant;
use App\Models\MerchantService;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerCheckoutService
{
    /**
     * @param  array<string, mixed>  $data
     *    Validated request: either service_public_id or (merchant_id, amount_minor, currency), optional description, idempotency pre-resolved.
     */
    public function createIntentForPayer(User $payer, array $data): PaymentIntent
    {
        if (! empty($data['service_public_id'])) {
            $service = MerchantService::query()
                ->where('public_id', $data['service_public_id'])
                ->where('status', MerchantService::STATUS_ACTIVE)
                ->with('merchant')
                ->first();
            if (! $service) {
                throw ValidationException::withMessages([
                    'service_public_id' => ['No active service found for this id.'],
                ]);
            }
            $merchant = $service->merchant;
            if (! $merchant) {
                throw ValidationException::withMessages([
                    'service_public_id' => ['Service has no merchant.'],
                ]);
            }
            $this->assertMerchantAcceptsPayer($payer, $merchant);
            $description = $data['description'] ?? $service->name;

            $gross = (int) $service->price_minor;

            return PaymentIntent::query()->create([
                'public_id' => (string) Str::uuid(),
                'merchant_id' => $merchant->id,
                'merchant_service_id' => $service->id,
                'payer_user_id' => $payer->id,
                'amount_minor' => $gross,
                'application_fee_minor' => PlatformApplicationFee::minorFromGross($gross),
                'currency' => strtoupper((string) $service->currency),
                'status' => PaymentIntentStatus::RequiresPayment,
                'description' => $description,
                'idempotency' => $data['idempotency'] ?? (string) Str::uuid(),
            ]);
        }

        $merchant = Merchant::query()->findOrFail($data['merchant_id']);
        $this->assertMerchantAcceptsPayer($payer, $merchant);
        $gross = (int) $data['amount_minor'];

        return PaymentIntent::query()->create([
            'public_id' => (string) Str::uuid(),
            'merchant_id' => $merchant->id,
            'merchant_service_id' => null,
            'payer_user_id' => $payer->id,
            'amount_minor' => $gross,
            'application_fee_minor' => PlatformApplicationFee::minorFromGross($gross),
            'currency' => strtoupper((string) $data['currency']),
            'status' => PaymentIntentStatus::RequiresPayment,
            'description' => $data['description'] ?? null,
            'idempotency' => $data['idempotency'] ?? (string) Str::uuid(),
        ]);
    }

    private function assertMerchantAcceptsPayer(User $payer, Merchant $merchant): void
    {
        if ($merchant->status !== 'active') {
            throw ValidationException::withMessages([
                'merchant_id' => ['This merchant is not accepting payments.'],
            ]);
        }
        if ((int) $merchant->user_id === (int) $payer->id) {
            abort(403, 'You cannot pay your own merchant through customer checkout.');
        }
    }
}
