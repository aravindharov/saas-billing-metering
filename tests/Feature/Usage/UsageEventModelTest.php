<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UsageEventModelTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Customer $customer;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forPlan($plan)
            ->create();
    }

    public function test_usage_event_belongs_to_merchant(): void
    {
        $event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->create();

        $this->assertEquals($this->merchant->id, $event->merchant->id);
    }

    public function test_usage_event_belongs_to_customer(): void
    {
        $event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->create();

        $this->assertEquals($this->customer->id, $event->customer->id);
    }

    public function test_usage_event_belongs_to_subscription(): void
    {
        $event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->create();

        $this->assertEquals($this->subscription->id, $event->subscription->id);
    }

    public function test_public_id_is_auto_generated(): void
    {
        $event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->create();

        $this->assertNotEmpty($event->public_id);
        $this->assertSame(26, strlen($event->public_id));
    }

    public function test_quantity_is_cast_to_integer(): void
    {
        $event = UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->create(['quantity' => 42]);

        $this->assertIsInt($event->quantity);
        $this->assertSame(42, $event->quantity);
    }

    public function test_route_key_is_public_id(): void
    {
        $event = new UsageEvent;
        $this->assertSame('public_id', $event->getRouteKeyName());
    }

    public function test_merchant_has_many_usage_events(): void
    {
        UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->count(3)
            ->create();

        $this->assertCount(3, $this->merchant->usageEvents);
    }

    public function test_subscription_has_many_usage_events(): void
    {
        UsageEvent::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forSubscription($this->subscription)
            ->count(2)
            ->create();

        $this->assertCount(2, $this->subscription->usageEvents);
    }
}
