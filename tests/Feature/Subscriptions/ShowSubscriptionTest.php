<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShowSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();

        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create([
            'base_price' => 9900,
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create();
    }

    public function test_owner_can_view_subscription(): void
    {
        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}"
        );

        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonPath('data.base_price', 9900);
        $response->assertJsonStructure(['data' => [
            'id', 'customer', 'plan', 'status', 'billing_cycle',
            'base_price', 'included_usage_units', 'overage_rate',
            'started_at', 'current_period_start', 'current_period_end',
            'plan_changes',
        ]]);
    }

    public function test_member_can_view_subscription(): void
    {
        $member = User::factory()->member()->forMerchant($this->merchant)->create();

        $response = $this->actingAs($member)->getJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}"
        );

        $response->assertOk();
    }

    public function test_show_includes_plan_change_history(): void
    {
        $toPlan = Plan::factory()->forMerchant($this->merchant)->create();
        SubscriptionPlanChange::factory()->create([
            'subscription_id' => $this->subscription->id,
            'from_plan_id' => $this->subscription->plan_id,
            'to_plan_id' => $toPlan->id,
        ]);

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}"
        );

        $response->assertOk();
        $response->assertJsonCount(1, 'data.plan_changes');
        $response->assertJsonStructure(['data' => [
            'plan_changes' => [['id', 'from_plan', 'to_plan', 'effective_at']],
        ]]);
    }

    public function test_cannot_view_subscription_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();
        $otherSub = Subscription::factory()
            ->forMerchant($otherMerchant)
            ->forCustomer($otherCustomer)
            ->forPlan($otherPlan)
            ->create();

        $response = $this->actingAs($this->owner)->getJson(
            "/api/v1/subscriptions/{$otherSub->public_id}"
        );

        $response->assertNotFound();
    }

    public function test_nonexistent_subscription_returns_404(): void
    {
        $response = $this->actingAs($this->owner)->getJson(
            '/api/v1/subscriptions/01NONEXISTENT000000000000'
        );

        $response->assertNotFound();
    }
}
