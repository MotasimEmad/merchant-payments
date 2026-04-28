<?php

namespace App\Services\Ledger;

use App\Enums\LedgerEntryCode;
use App\Models\LedgerBatch;
use App\Models\LedgerLine;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LedgerService
{
    /**
     * @param  list<array{wallet_id: int, cents: int, type: LedgerEntryCode|string, link_type?: string|null, link_id?: int|null, extra?: array<string, mixed>|null}>  $lines
     */
    public function postBatch(
        ?string $idempotencyKey,
        string $name,
        string $channel,
        array $lines,
    ): LedgerBatch {
        if ($idempotencyKey) {
            $existing = LedgerBatch::query()->where('idempotency', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        if (count($lines) < 2) {
            throw new RuntimeException('A ledger batch must contain at least two lines (double-entry).');
        }

        $sum = 0;
        foreach ($lines as $line) {
            $sum += $line['cents'];
        }
        if ($sum !== 0) {
            throw new RuntimeException("Ledger batch is not balanced: sum of cents is {$sum}, expected 0.");
        }

        $walletIds = array_unique(array_column($lines, 'wallet_id'));
        $wallets = Wallet::query()->whereIn('id', $walletIds)->where('status', 'active')->get();
        if ($wallets->count() !== count($walletIds)) {
            throw new RuntimeException('One or more wallets are missing or inactive.');
        }

        return DB::transaction(function () use ($idempotencyKey, $name, $channel, $lines) {
            $batch = LedgerBatch::query()->create([
                'ref' => (string) Str::uuid(),
                'idempotency' => $idempotencyKey,
                'name' => $name,
                'channel' => $channel,
                'status' => 'posted',
            ]);

            foreach ($lines as $row) {
                $code = $row['type'] instanceof LedgerEntryCode
                    ? $row['type']->value
                    : (string) $row['type'];

                LedgerLine::query()->create([
                    'batch_id' => $batch->id,
                    'wallet_id' => $row['wallet_id'],
                    'cents' => $row['cents'],
                    'type' => $code,
                    'link_type' => $row['link_type'] ?? null,
                    'link_id' => $row['link_id'] ?? null,
                    'extra' => $row['extra'] ?? null,
                ]);
            }

            return $batch->load('lines');
        });
    }

    public function balanceMinor(Wallet $wallet): int
    {
        return (int) LedgerLine::query()
            ->where('wallet_id', $wallet->id)
            ->sum('cents');
    }
}
