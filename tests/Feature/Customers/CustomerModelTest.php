<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_belongs_to_merchant(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();

        $this->assertEquals($merchant->id, $customer->merchant->id);
    }

    public function test_merchant_has_many_customers(): void
    {
        $merchant = Merchant::factory()->create();
        Customer::factory()->forMerchant($merchant)->count(3)->create();

        $this->assertCount(3, $merchant->customers);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(CustomerStatus::class, $customer->status);
        $this->assertSame(CustomerStatus::Active, $customer->status);
    }

    public function test_public_id_is_auto_generated(): void
    {
        $customer = Customer::factory()->create();

        $this->assertNotEmpty($customer->public_id);
        $this->assertSame(26, strlen($customer->public_id));
    }

    public function test_route_key_is_public_id(): void
    {
        $customer = new Customer;

        $this->assertSame('public_id', $customer->getRouteKeyName());
    }

    public function test_is_active_returns_correctly(): void
    {
        $active = Customer::factory()->create();
        $inactive = Customer::factory()->inactive()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_is_inactive_returns_correctly(): void
    {
        $active = Customer::factory()->create();
        $inactive = Customer::factory()->inactive()->create();

        $this->assertFalse($active->isInactive());
        $this->assertTrue($inactive->isInactive());
    }

    public function test_factory_with_external_reference_state(): void
    {
        $customer = Customer::factory()->withExternalReference('CRM-99')->create();

        $this->assertSame('CRM-99', $customer->external_reference);
    }
}
