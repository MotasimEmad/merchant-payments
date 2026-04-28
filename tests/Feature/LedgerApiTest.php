<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payments\WalletOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_creates_balanced_ledger_and_increases_user_balance(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $user = User::query()->where('email', 'customer@payinfra.local')->firstOrFail();
        $wallet = app(WalletOperationService::class)->getOrCreateWalletForUser($user, 'USD');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson(
            "/api/v1/wallets/{$wallet->uuid}/deposits",
            [
                'amount_minor' => 1234,
                'currency' => 'USD',
                'idempotency' => 'test-deposit-1',
            ],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        );

        $response->assertCreated();
        $this->assertDatabaseHas('ledger_batches', ['idempotency' => 'test-deposit-1']);

        $list = $this->getJson('/api/v1/wallets', [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ]);
        $list->assertOk();
        $w = collect($list->json('wallets'))->firstWhere('uuid', $wallet->uuid);
        $this->assertNotNull($w);
        $this->assertSame(1234, (int) $w['balance_minor']);
    }
}
