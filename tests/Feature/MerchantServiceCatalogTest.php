<?php

namespace Tests\Feature;

use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_lists_active_merchant_services_by_public_id(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);
        $merchant = Merchant::query()->where('business_name', 'Cedar Street Coffee')->firstOrFail();

        $r = $this->getJson('/api/v1/merchants/'.$merchant->public_id.'/services');
        $r->assertOk();
        $r->assertJsonPath('merchant.business_name', 'Cedar Street Coffee');
        $r->assertJsonPath('merchant.public_id', $merchant->public_id);
        $this->assertGreaterThanOrEqual(1, count($r->json('services')));
    }
}
