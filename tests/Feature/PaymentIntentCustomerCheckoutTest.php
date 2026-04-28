<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use App\Services\Payments\WalletOperationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIntentCustomerCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_creates_checkout_intent_and_confirms_payment(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $customer = User::query()->where('email', 'customer@payinfra.local')->firstOrFail();
        $ops = app(WalletOperationService::class);
        $ops->deposit($customer, 10_000, 'USD', 'funding-for-checkout', 'test');

        $token = $customer->createToken('test')->plainTextToken;
        $merchant = Merchant::query()->where('business_name', 'Cedar Street Coffee')->firstOrFail();

        $create = $this->postJson(
            '/api/v1/payment_intents/checkout',
            [
                'merchant_id' => $merchant->id,
                'amount_minor' => 2_000,
                'currency' => 'USD',
                'description' => 'Order #1',
                'idempotency' => 'checkout-intent-1',
            ],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        );
        $create->assertCreated();
        $this->assertSame('requires_payment', $create->json('status'));
        $this->assertSame($customer->id, $create->json('payer_user_id'));
        $this->assertSame(100, (int) $create->json('application_fee_minor'));
        $publicId = $create->json('public_id');
        $this->assertIsString($publicId);

        $confirm = $this->postJson(
            "/api/v1/payment_intents/{$publicId}/confirm",
            ['idempotency' => 'confirm-1'],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        );
        $confirm->assertOk();
        $this->assertSame('succeeded', $confirm->json('status'));
        $this->assertNotNull($confirm->json('batch_ref'));
    }

    public function test_customer_cannot_checkout_to_own_merchant(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $owner = User::query()->where('email', 'merchant@payinfra.local')->firstOrFail();
        $token = $owner->createToken('test')->plainTextToken;
        $merchant = Merchant::query()->where('user_id', $owner->id)->firstOrFail();

        $this->postJson(
            '/api/v1/payment_intents/checkout',
            [
                'merchant_id' => $merchant->id,
                'amount_minor' => 100,
                'currency' => 'USD',
            ],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        )->assertStatus(403);
    }

    public function test_customer_checkout_with_service_public_id_uses_price_from_catalog(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $customer = User::query()->where('email', 'customer@payinfra.local')->firstOrFail();
        $ops = app(WalletOperationService::class);
        $ops->deposit($customer, 100_00, 'USD', 'funding-service-checkout', 'test');

        $token = $customer->createToken('test')->plainTextToken;
        $service = Merchant::query()
            ->where('business_name', 'Cedar Street Coffee')
            ->firstOrFail()
            ->services()
            ->where('name', 'Pastry box')
            ->firstOrFail();

        $create = $this->postJson(
            '/api/v1/payment_intents/checkout',
            [
                'service_public_id' => $service->public_id,
            ],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        );
        $create->assertCreated();
        $this->assertSame((int) $service->price_minor, (int) $create->json('amount_minor'));
        $this->assertNotNull($create->json('service'));
        $this->assertSame('Pastry box', $create->json('service.name'));
        $this->assertSame(
            (int) \App\Services\Payments\PlatformApplicationFee::minorFromGross((int) $service->price_minor),
            (int) $create->json('application_fee_minor')
        );
        $this->assertIsInt($create->json('merchant_service_id'));

        $this->postJson(
            '/api/v1/payment_intents/'.$create->json('public_id').'/confirm',
            ['idempotency' => 'svc-confirm-1'],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        )->assertOk();
    }

    public function test_checkout_fails_for_inactive_merchant(): void
    {
        $this->seed(\Database\Seeders\SystemAccountsSeeder::class);
        $this->seed(\Database\Seeders\PlatformSeeder::class);

        $customer = User::query()->where('email', 'customer@payinfra.local')->firstOrFail();
        $token = $customer->createToken('test')->plainTextToken;
        $merchant = Merchant::query()->where('business_name', 'Cedar Street Coffee')->firstOrFail();
        $merchant->update(['status' => 'inactive']);

        $this->postJson(
            '/api/v1/payment_intents/checkout',
            [
                'merchant_id' => $merchant->id,
                'amount_minor' => 100,
                'currency' => 'USD',
            ],
            ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json']
        )->assertStatus(422);
    }
}
