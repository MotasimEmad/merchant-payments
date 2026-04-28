<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LedgerLine;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }
        $walletIds = $this->walletIdsForUser($user);
        $query = LedgerLine::query()
            ->with(['batch', 'wallet'])
            ->whereIn('wallet_id', $walletIds)
            ->orderByDesc('id');

        if ($request->query('wallet_uuid')) {
            $w = Wallet::query()->where('uuid', $request->query('wallet_uuid'))->first();
            if ($w) {
                $this->authorizeWallet($user, $w);
                $query->where('wallet_id', $w->id);
            }
        }

        $rows = $query->limit(100)->get()->map(function (LedgerLine $line) {
            return [
                'id' => $line->id,
                'cents' => $line->cents,
                'type' => $line->type,
                'extra' => $line->extra,
                'batch' => [
                    'ref' => $line->batch?->ref,
                    'name' => $line->batch?->name,
                    'created_at' => $line->batch?->created_at?->toIso8601String(),
                ],
                'wallet_uuid' => $line->wallet?->uuid,
                'created_at' => $line->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['lines' => $rows]);
    }

    /**
     * @return list<int>
     */
    private function walletIdsForUser(User $user): array
    {
        $ids = Wallet::query()
            ->where('holder_type', User::class)
            ->where('holder_id', $user->id)
            ->pluck('id');
        $mIds = $user->merchants()->pluck('id');
        if ($mIds->isNotEmpty()) {
            $ids = $ids->merge(
                Wallet::query()
                    ->where('holder_type', Merchant::class)
                    ->whereIn('holder_id', $mIds)
                    ->pluck('id')
            );
        }

        return $ids->unique()->values()->all();
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
}
