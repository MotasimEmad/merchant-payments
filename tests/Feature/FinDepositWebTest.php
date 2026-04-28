<?php

namespace Tests\Feature;

use App\Models\LedgerLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinDepositWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_post_deposit_and_see_batch(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $user = User::query()->where('email', 'flow@payinfra.local')->firstOrFail();
        $countBefore = LedgerLine::query()->count();

        $this->actingAs($user)
            ->post(route('pay.deposit.store'), [
                'amount' => '12.34',
                'currency' => 'USD',
                'idempotency' => 'test-web-1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ledger_batches', [
            'idempotency' => 'test-web-1',
        ]);
        $this->assertSame($countBefore + 2, LedgerLine::query()->count());
    }
}
