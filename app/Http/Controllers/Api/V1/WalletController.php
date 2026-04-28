<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\WalletOperationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletOperationService $wallets,
        private readonly LedgerService $ledger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $wallets = $this->queryAccessibleWallets($user)->get()->map(fn (Wallet $w) => $this->walletArray($w));

        return response()->json(['wallets' => $wallets]);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $user = $this->user($request);
        $wallet = Wallet::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorizeWallet($user, $wallet);

        return response()->json(['wallet' => $this->walletArray($wallet)]);
    }

    public function deposit(Request $request, string $uuid): JsonResponse
    {
        $user = $this->user($request);
        $wallet = Wallet::query()->where('uuid', $uuid)->firstOrFail();
        if ($wallet->holder_type !== User::class || (int) $wallet->holder_id !== (int) $user->id) {
            return response()->json(['message' => 'Deposits are only to your personal wallet.'], 422);
        }
        $data = $request->validate([
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'idempotency' => 'required|string|max:120',
        ]);
        if (strtoupper($data['currency']) !== $wallet->currency) {
            return response()->json(['message' => 'Currency must match the wallet.'], 422);
        }
        try {
            $batch = $this->wallets->deposit($user, (int) $data['amount_minor'], $data['currency'], $data['idempotency'], 'api');
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'batch_ref' => $batch->ref,
        ], 201);
    }

    public function withdraw(Request $request, string $uuid): JsonResponse
    {
        $user = $this->user($request);
        $wallet = Wallet::query()->where('uuid', $uuid)->firstOrFail();
        if ($wallet->holder_type !== User::class || (int) $wallet->holder_id !== (int) $user->id) {
            return response()->json(['message' => 'Withdrawals are only from your personal wallet.'], 422);
        }
        $data = $request->validate([
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'idempotency' => 'required|string|max:120',
        ]);
        if (strtoupper($data['currency']) !== $wallet->currency) {
            return response()->json(['message' => 'Currency must match the wallet.'], 422);
        }
        try {
            $batch = $this->wallets->withdraw($user, (int) $data['amount_minor'], $data['currency'], $data['idempotency'], 'api');
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['batch_ref' => $batch->ref], 201);
    }

    public function transfer(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'to_email' => 'required|email|exists:users,email',
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|size:3',
            'idempotency' => 'required|string|max:120',
        ]);
        if ($data['to_email'] === $user->email) {
            return response()->json(['message' => 'Use another user as the recipient.'], 422);
        }
        $to = User::query()->where('email', $data['to_email'])->firstOrFail();
        try {
            $batch = $this->wallets->transfer(
                $user,
                $to,
                (int) $data['amount_minor'],
                $data['currency'],
                $data['idempotency'],
                'api',
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['batch_ref' => $batch->ref], 201);
    }

    private function user(Request $request): User
    {
        $u = $request->user();
        if (! $u instanceof User) {
            abort(401);
        }

        return $u;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Wallet>
     */
    private function queryAccessibleWallets(User $user)
    {
        return Wallet::query()->where(function ($q) use ($user) {
            $q->where('holder_type', User::class)->where('holder_id', $user->id);
        })->orWhere(function ($q) use ($user) {
            $mIds = $user->merchants()->pluck('id');
            if ($mIds->isEmpty()) {
                $q->whereRaw('0 = 1');
            } else {
                $q->where('holder_type', Merchant::class)->whereIn('holder_id', $mIds);
            }
        });
    }

    private function authorizeWallet(User $user, Wallet $wallet): void
    {
        if ($wallet->holder_type === User::class && (int) $wallet->holder_id === (int) $user->id) {
            return;
        }
        if ($wallet->holder_type === Merchant::class) {
            $m = Merchant::query()->find($wallet->holder_id);
            if ($m && (int) $m->user_id === (int) $user->id) {
                return;
            }
        }
        abort(403);
    }

    /**
     * @return array<string, mixed>
     */
    private function walletArray(Wallet $w): array
    {
        return [
            'uuid' => $w->uuid,
            'currency' => $w->currency,
            'label' => $w->label,
            'status' => $w->status,
            'holder_type' => class_basename($w->holder_type),
            'balance_minor' => $this->ledger->balanceMinor($w),
        ];
    }
}
