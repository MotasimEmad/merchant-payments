<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\PlatformAccount;
use App\Services\Ledger\LedgerService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PlatformAccountWebController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function index(): InertiaResponse
    {
        $rows = PlatformAccount::query()
            ->orderBy('key')
            ->get()
            ->map(function (PlatformAccount $a) {
                $wallets = $a->wallets()->get()->map(function ($w) {
                    return [
                        'uuid' => $w->uuid,
                        'currency' => $w->currency,
                        'balance_cents' => $this->ledger->balanceMinor($w),
                    ];
                });

                return [
                    'key' => $a->key,
                    'label' => $a->label,
                    'description' => $a->description,
                    'wallets' => $wallets,
                ];
            });

        return Inertia::render('Fin/PlatformAccounts', [
            'accounts' => $rows,
        ]);
    }
}
