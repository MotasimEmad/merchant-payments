<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\MerchantService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PlatformSeeder extends Seeder
{
    /**
     * Test users (password: password, verified) plus one merchant row for merchant@payinfra.local. No seed deposits.
     */
    public function run(): void
    {
        $users = [
            ['flow@payinfra.local', 'Flow Tester', 'customer'],
            ['customer@payinfra.local', 'Avery Customer', 'customer'],
            ['merchant@payinfra.local', 'Morgan Merchant', 'merchant'],
        ];
        foreach ($users as [$email, $name, $role]) {
            $u = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                ],
            );
            if ($u->email_verified_at === null) {
                $u->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $owner = User::query()->where('email', 'merchant@payinfra.local')->firstOrFail();
        $merchant = Merchant::query()->firstOrCreate(
            ['user_id' => $owner->id],
            [
                'public_id' => (string) Str::uuid(),
                'business_name' => 'Cedar Street Coffee',
                'status' => 'active',
            ],
        );
        $catalog = [
            [
                'name' => 'Latte subscription (monthly)',
                'description' => 'Unlimited drip plus one specialty drink per day.',
                'price_minor' => 29_99,
            ],
            [
                'name' => 'Pastry box',
                'description' => 'Assorted baked goods for the week.',
                'price_minor' => 12_50,
            ],
        ];
        foreach ($catalog as $row) {
            $merchant->services()->firstOrCreate(
                ['name' => $row['name']],
                [
                    'public_id' => (string) Str::uuid(),
                    'description' => $row['description'],
                    'price_minor' => $row['price_minor'],
                    'currency' => 'USD',
                    'status' => MerchantService::STATUS_ACTIVE,
                    'sort_order' => 0,
                ],
            );
        }
    }
}
