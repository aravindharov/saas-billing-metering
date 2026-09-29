<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ListSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
    }

    public function test_owner_can_list_subscriptions(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer)->forPlan($plan)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/subscriptions');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure([
            'data' => [['id', 'status', 'customer', 'plan', 'base_price']],
            'meta',
        ]);
    }

    public function test_member_can_list_subscriptions(): void
    {
        $response = $this->actingAs($this->member)->getJson('/api/v1/subscriptions');

        $response->assertOk();
    }

    public function test_can_filter_by_status(): void
    {
        $customer1 = Customer::factory()->forMerchant($this->merchant)->create();
        $customer2 = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer1)->forPlan($plan)->create();
        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer2)->forPlan($plan)->cancelled()->create();

        $active = $this->actingAs($this->owner)->getJson('/api/v1/subscriptions?status=active');
        $active->assertOk();
        $active->assertJsonCount(1, 'data');

        $cancelled = $this->actingAs($this->owner)->getJson('/api/v1/subscriptions?status=cancelled');
        $cancelled->assertOk();
        $cancelled->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_customer_id(): void
    {
        $customer1 = Customer::factory()->forMerchant($this->merchant)->create();
        $customer2 = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();

        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer1)->forPlan($plan)->create();
        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer2)->forPlan($plan)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/subscriptions?customer_id={$customer1->public_id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_plan_id(): void
    {
        $customer1 = Customer::factory()->forMerchant($this->merchant)->create();
        $customer2 = Customer::factory()->forMerchant($this->merchant)->create();
        $plan1 = Plan::factory()->forMerchant($this->merchant)->create();
        $plan2 = Plan::factory()->forMerchant($this->merchant)->create();

        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer1)->forPlan($plan1)->create();
        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer2)->forPlan($plan2)->create();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/v1/subscriptions?plan_id={$plan1->public_id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_cannot_see_subscriptions_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();
        Subscription::factory()->forMerchant($otherMerchant)
            ->forCustomer($otherCustomer)->forPlan($otherPlan)->create();

        $response = $this->actingAs($this->owner)->getJson('/api/v1/subscriptions');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_unauthenticated_user_cannot_list_subscriptions(): void
    {
        $response = $this->getJson('/api/v1/subscriptions');

        $response->assertUnauthorized();
    }
}
