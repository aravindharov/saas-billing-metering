<?php

declare(strict_types=1);

namespace Tests\Feature\Subscriptions;

use App\Enums\BillingCycle;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ChangeSubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Subscription $subscription;

    private Plan $targetPlan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();

        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $starterPlan = Plan::factory()->forMerchant($this->merchant)->create([
            'name' => 'Starter',
            'base_price' => 9900,
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($starterPlan)
            ->create();

        $this->targetPlan = Plan::factory()->forMerchant($this->merchant)->create([
            'name' => 'Professional',
            'base_price' => 49900,
            'included_usage_units' => 10000,
            'overage_rate' => 3,
        ]);
    }

    public function test_owner_can_change_plan(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        );

        $response->assertOk();
        $response->assertJsonPath('data.to_base_price', 49900);
        $response->assertJsonPath('data.to_included_usage_units', 10000);
        $response->assertJsonPath('data.to_overage_rate', 3);
        $response->assertJsonPath('data.from_base_price', 9900);
        $response->assertJsonPath('data.from_included_usage_units', 1000);
        $response->assertJsonPath('data.from_overage_rate', 5);
        $response->assertJsonStructure(['data' => [
            'id', 'from_plan', 'to_plan', 'effective_at',
            'from_base_price', 'to_base_price',
        ]]);
    }

    public function test_plan_change_updates_subscription_pricing_snapshot(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        )->assertOk();

        $this->subscription->refresh();
        $this->assertSame(49900, $this->subscription->base_price);
        $this->assertSame(10000, $this->subscription->included_usage_units);
        $this->assertSame(3, $this->subscription->overage_rate);
        $this->assertSame($this->targetPlan->id, $this->subscription->plan_id);
    }

    public function test_plan_change_preserves_historical_pricing(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        )->assertOk();

        $this->assertDatabaseHas('subscription_plan_changes', [
            'subscription_id' => $this->subscription->id,
            'from_base_price' => 9900,
            'from_included_usage_units' => 1000,
            'from_overage_rate' => 5,
            'to_base_price' => 49900,
            'to_included_usage_units' => 10000,
            'to_overage_rate' => 3,
        ]);
    }

    public function test_plan_change_history_is_independent_of_plan_edits(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        )->assertOk();

        $this->targetPlan->update(['base_price' => 99900]);

        $this->assertDatabaseHas('subscription_plan_changes', [
            'subscription_id' => $this->subscription->id,
            'to_base_price' => 49900,
        ]);
    }

    public function test_member_cannot_change_plan(): void
    {
        $response = $this->actingAs($this->member)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        );

        $response->assertForbidden();
    }

    public function test_cannot_change_to_same_plan(): void
    {
        $currentPlan = $this->subscription->plan;

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $currentPlan->public_id]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_cannot_change_plan_on_cancelled_subscription(): void
    {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $cancelled = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$cancelled->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        );

        $response->assertUnprocessable();
    }

    public function test_cannot_change_to_archived_plan(): void
    {
        $archived = Plan::factory()->forMerchant($this->merchant)->archived()->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $archived->public_id]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_cannot_change_to_plan_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $otherPlan->public_id]
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_plan_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            []
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_multiple_plan_changes_create_historical_segments(): void
    {
        $thirdPlan = Plan::factory()->forMerchant($this->merchant)->create([
            'name' => 'Enterprise',
            'base_price' => 99900,
        ]);

        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        )->assertOk();

        $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}/change-plan",
            ['plan_id' => $thirdPlan->public_id]
        )->assertOk();

        $showResponse = $this->actingAs($this->owner)->getJson(
            "/api/v1/subscriptions/{$this->subscription->public_id}"
        );

        $showResponse->assertOk();
        $changes = $showResponse->json('data.plan_changes');
        $this->assertCount(2, $changes);
    }

    public function test_subscription_from_another_merchant_returns_404(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();
        $otherSub = Subscription::factory()
            ->forMerchant($otherMerchant)
            ->forCustomer($otherCustomer)
            ->forPlan($otherPlan)
            ->create();

        $response = $this->actingAs($this->owner)->postJson(
            "/api/v1/subscriptions/{$otherSub->public_id}/change-plan",
            ['plan_id' => $this->targetPlan->public_id]
        );

        $response->assertNotFound();
    }
}
