<?php

namespace Database\Seeders;

use App\Models\PlatformAccount;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemAccountsSeeder extends Seeder
{
    /**
     * Platform double-entry side accounts and their USD wallets (no users, no deposits).
     */
    public function run(): void
    {
        foreach (['clearing' => 'External funding / settlement', 'fees' => 'Platform application fees', 'settlement' => 'Merchant payout float'] as $key => $label) {
            $a = PlatformAccount::query()->firstOrCreate(
                ['key' => $key],
                [
                    'label' => $label,
                    'description' => 'Internal ledger side for double-entry',
                ],
            );
            if (! $a->wallets()->where('currency', 'USD')->exists()) {
                Wallet::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'holder_type' => PlatformAccount::class,
                    'holder_id' => $a->id,
                    'currency' => 'USD',
                    'label' => 'USD '.$a->key,
                    'status' => 'active',
                ]);
            }
        }
    }
}
