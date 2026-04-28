<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\LedgerLine;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Ledger\LedgerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FinDashboardController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $wallets = $this->walletIds($user);
        $walletRows = $wallets->map(function (Wallet $w) {
            return [
                'uuid' => $w->uuid,
                'label' => $w->label,
                'currency' => $w->currency,
                'holder' => class_basename($w->holder_type),
                'balance_minor' => $this->ledger->balanceMinor($w),
            ];
        })->values()->all();
        $lines = LedgerLine::query()
            ->whereIn('wallet_id', $wallets->pluck('id'))
            ->with('batch')
            ->orderByDesc('id')
            ->limit(40)
            ->get()
            ->map(function (LedgerLine $line) {
                return [
                    'id' => $line->id,
                    'cents' => $line->cents,
                    'type' => $line->type,
                    'batch_name' => $line->batch?->name,
                    'batch_ref' => $line->batch?->ref,
                ];
            })->all();

        return Inertia::render('Fin/Dashboard', [
            'wallets' => $walletRows,
            'ledgerPreview' => $lines,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Wallet>
     */
    private function walletIds(User $user)
    {
        $a = Wallet::query()
            ->where('holder_type', User::class)
            ->where('holder_id', $user->id)
            ->get();
        $mIds = $user->merchants()->pluck('id');
        if ($mIds->isEmpty()) {
            return $a;
        }
        $b = Wallet::query()
            ->where('holder_type', Merchant::class)
            ->whereIn('holder_id', $mIds)
            ->get();

        return $a->merge($b);
    }
}
