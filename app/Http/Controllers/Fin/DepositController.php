<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Payments\WalletOperationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Str;
use Throwable;

class DepositController extends Controller
{
    public function __construct(
        private readonly WalletOperationService $wallets,
    ) {}

    public function create(Request $request): InertiaResponse
    {
        $user = $this->user($request);
        $wallet = $this->wallets->getOrCreateWalletForUser($user, 'USD');

        return Inertia::render('Fin/Deposit', [
            'wallet' => [
                'uuid' => $wallet->uuid,
                'currency' => $wallet->currency,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:999999.99',
            'currency' => 'required|in:USD',
            'idempotency' => 'nullable|string|max:120',
        ]);

        $cents = (int) round(((float) $data['amount']) * 100);
        if ($cents < 1) {
            return back()->withErrors(['amount' => 'Amount is too small.'])->withInput();
        }
        $idempotency = $data['idempotency'] !== null && $data['idempotency'] !== ''
            ? $data['idempotency']
            : 'web-'.Str::uuid()->toString();

        try {
            $batch = $this->wallets->deposit($user, $cents, $data['currency'], $idempotency, 'web');
        } catch (Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('pay.batches.show', ['ref' => $batch->ref])
            ->with('flash', [
                'message' => 'Deposit completed. This batch balances to zero (your wallet + platform clearing).',
            ]);
    }

    private function user(Request $request): User
    {
        $u = $request->user();
        if (! $u instanceof User) {
            abort(401);
        }

        return $u;
    }
}
