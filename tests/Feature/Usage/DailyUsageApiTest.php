<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DailyUsageApiTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
    }

    public function test_owner_can_list_daily_usage(): void
    {
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forDate('2026-09-29')
            ->create(['total_quantity' => 600]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.total_quantity', 600);
        $response->assertJsonPath('data.0.usage_date', '2026-09-29');
        $response->assertJsonStructure(['data' => [['customer', 'usage_date', 'total_quantity']]]);
    }

    public function test_member_can_list_daily_usage(): void
    {
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forDate('2026-09-29')
            ->create(['total_quantity' => 100]);

        $response = $this->actingAs($this->member)->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_date_range(): void
    {
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-09-28')->create();
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-09-29')->create();
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-09-30')->create();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/usage/daily?from=2026-09-28&to=2026-09-29');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_can_filter_by_customer(): void
    {
        $other = Customer::factory()->forMerchant($this->merchant)->create();

        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-09-29')
            ->create(['total_quantity' => 100]);
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($other)->forDate('2026-09-29')
            ->create(['total_quantity' => 200]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/usage/daily?date=2026-09-29&customer_id={$this->customer->public_id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.total_quantity', 100);
    }

    public function test_tenant_isolation(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();

        DailyUsage::factory()->forMerchant($other)
            ->forCustomer($otherCustomer)->forDate('2026-09-29')
            ->create(['total_quantity' => 9999]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertUnauthorized();
    }

    public function test_internal_ids_not_exposed(): void
    {
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-09-29')
            ->create(['total_quantity' => 100]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertOk();
        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('merchant_id', $data);
        $this->assertArrayNotHasKey('customer_id', $data);
    }

    public function test_default_date_range_limits_results(): void
    {
        // Old record outside default 31-day window.
        DailyUsage::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forDate('2026-01-01')
            ->create(['total_quantity' => 100]);

        $response = $this->actingAs($this->owner)->getJson('/api/v1/usage/daily');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_paginated_response(): void
    {
        $response = $this->actingAs($this->owner)->getJson('/api/v1/usage/daily?date=2026-09-29');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_rejects_unbounded_date_range(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/usage/daily?from=2020-01-01&to=2026-12-31');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('to');
    }
}
