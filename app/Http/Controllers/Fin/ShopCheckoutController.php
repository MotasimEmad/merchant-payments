<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Payments\CustomerCheckoutService;
use App\Services\Payments\WalletOperationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class ShopCheckoutController extends Controller
{
    public function __construct(
        private readonly CustomerCheckoutService $customerCheckout,
        private readonly WalletOperationService $wallets,
    ) {}

    public function payService(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        $data = $request->validate([
            'service_public_id' => 'required|uuid|exists:merchant_services,public_id',
        ]);
        $idemp = (string) Str::uuid();
        $intent = $this->customerCheckout->createIntentForPayer($user, [
            'service_public_id' => $data['service_public_id'],
            'idempotency' => $idemp,
        ]);
        try {
            $this->wallets->capturePaymentIntent(
                $intent,
                "pi_web_{$intent->public_id}_{$idemp}",
                'web',
            );
        } catch (Throwable $e) {
            return back()->withErrors(['service_public_id' => $e->getMessage()]);
        }
        $intent->refresh();

        $ref = $intent->ledgerBatch?->ref;
        if (! $ref) {
            return redirect()->route('pay.shops');
        }

        return redirect()->route('pay.batches.show', ['ref' => $ref])
            ->with('flash', 'Payment complete.');
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
