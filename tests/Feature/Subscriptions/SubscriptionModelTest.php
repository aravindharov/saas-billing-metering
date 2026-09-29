<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_belongs_to_merchant(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertEquals($merchant->id, $sub->merchant->id);
    }

    public function test_subscription_belongs_to_customer(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertEquals($customer->id, $sub->customer->id);
    }

    public function test_subscription_belongs_to_plan(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertEquals($plan->id, $sub->plan->id);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertInstanceOf(SubscriptionStatus::class, $sub->status);
    }

    public function test_billing_cycle_is_cast_to_enum(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertInstanceOf(BillingCycle::class, $sub->billing_cycle);
    }

    public function test_base_price_is_cast_to_integer(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create(['base_price' => 49900]);

        $this->assertIsInt($sub->base_price);
        $this->assertSame(49900, $sub->base_price);
    }

    public function test_public_id_is_auto_generated(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();
        $sub = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $this->assertNotEmpty($sub->public_id);
        $this->assertSame(26, strlen($sub->public_id));
    }

    public function test_route_key_is_public_id(): void
    {
        $sub = new Subscription;

        $this->assertSame('public_id', $sub->getRouteKeyName());
    }

    public function test_is_active_returns_correctly(): void
    {
        $merchant = Merchant::factory()->create();
        $customer1 = Customer::factory()->forMerchant($merchant)->create();
        $customer2 = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();

        $active = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer1)->forPlan($plan)->create();
        $cancelled = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer2)->forPlan($plan)->cancelled()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($cancelled->isActive());
    }

    public function test_is_cancelled_returns_correctly(): void
    {
        $merchant = Merchant::factory()->create();
        $customer1 = Customer::factory()->forMerchant($merchant)->create();
        $customer2 = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();

        $active = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer1)->forPlan($plan)->create();
        $cancelled = Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer2)->forPlan($plan)->cancelled()->create();

        $this->assertFalse($active->isCancelled());
        $this->assertTrue($cancelled->isCancelled());
    }

    public function test_merchant_has_many_subscriptions(): void
    {
        $merchant = Merchant::factory()->create();
        $customer1 = Customer::factory()->forMerchant($merchant)->create();
        $customer2 = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();

        Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer1)->forPlan($plan)->create();
        Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer2)->forPlan($plan)->create();

        $this->assertCount(2, $merchant->subscriptions);
    }

    public function test_customer_has_many_subscriptions(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan1 = Plan::factory()->forMerchant($merchant)->create();
        $plan2 = Plan::factory()->forMerchant($merchant)->create();

        Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan1)->cancelled()->create();
        Subscription::factory()->forMerchant($merchant)
            ->forCustomer($customer)->forPlan($plan2)->create();

        $this->assertCount(2, $customer->subscriptions);
    }
}
