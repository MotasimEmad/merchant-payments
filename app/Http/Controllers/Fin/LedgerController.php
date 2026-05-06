<?php

namespace App\Http\Controllers\Fin;

use App\Http\Controllers\Controller;
use App\Models\LedgerBatch;
use App\Models\LedgerLine;
use App\Models\Merchant;
use App\Models\PlatformAccount;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LedgerController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $this->user($request);
        $wallets = $this->walletsForUser($user);
        $lines = LedgerLine::query()
            ->whereIn('wallet_id', $wallets->pluck('id'))
            ->with(['batch', 'wallet.holder'])
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function (LedgerLine $line) {
                $holder = $line->wallet?->holder;
                $holderLabel = '—';
                if ($holder instanceof User) {
                    $holderLabel = 'User: '.$holder->email;
                } elseif ($holder instanceof Merchant) {
                    $holderLabel = 'Merchant: '.$holder->business_name;
                } elseif ($holder instanceof PlatformAccount) {
                    $holderLabel = 'Platform: '.$holder->key;
                }

                return [
                    'id' => $line->id,
                    'cents' => $line->cents,
                    'type' => $line->type,
                    'extra' => $line->extra,
                    'holder_label' => $holderLabel,
                    'batch' => [
                        'ref' => $line->batch?->ref,
                        'name' => $line->batch?->name,
                        'channel' => $line->batch?->channel,
                    ],
                ];
            });

        return Inertia::render('Fin/Ledger', [
            'lines' => $lines,
        ]);
    }

    public function showBatch(Request $request, string $ref): InertiaResponse
    {
        $user = $this->user($request);
        $batch = LedgerBatch::query()->where('ref', $ref)->firstOrFail();
        $lines = LedgerLine::query()
            ->where('batch_id', $batch->id)
            ->with('wallet.holder')
            ->get();

        $userWalletIds = $this->walletsForUser($user)->pluck('id');
        if ($lines->pluck('wallet_id')->intersect($userWalletIds)->isEmpty()) {
            abort(403, 'This batch is not related to your wallets.');
        }

        $mapped = $lines->map(function (LedgerLine $line) {
            $h = $line->wallet?->holder;
            $label = '—';
            if ($h instanceof User) {
                $label = 'User: '.$h->email;
            } elseif ($h instanceof Merchant) {
                $label = 'Merchant: '.$h->business_name;
            } elseif ($h instanceof PlatformAccount) {
                $label = 'Platform: '.$h->key;
            }

            return [
                'cents' => $line->cents,
                'type' => $line->type,
                'holder' => $label,
            ];
        });

        return Inertia::render('Fin/BatchShow', [
            'batch' => [
                'ref' => $batch->ref,
                'name' => $batch->name,
                'channel' => $batch->channel,
            ],
            'lines' => $mapped,
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

    /**
     * @return \Illuminate\Support\Collection<int, Wallet>
     */
    private function walletsForUser(User $user)
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
