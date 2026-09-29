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

final class CreateSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Customer $customer;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
        $this->plan = Plan::factory()->forMerchant($this->merchant)->create([
            'base_price' => 9900,
            'included_usage_units' => 1000,
            'overage_rate' => 5,
            'billing_cycle' => BillingCycle::Monthly,
        ]);
    }

    public function test_owner_can_create_subscription(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'active');
        $response->assertJsonPath('data.billing_cycle', 'monthly');
        $response->assertJsonPath('data.base_price', 9900);
        $response->assertJsonPath('data.included_usage_units', 1000);
        $response->assertJsonPath('data.overage_rate', 5);
        $response->assertJsonStructure(['data' => [
            'id', 'status', 'billing_cycle', 'base_price',
            'included_usage_units', 'overage_rate',
            'started_at', 'current_period_start', 'current_period_end',
            'customer', 'plan', 'created_at', 'updated_at',
        ]]);
    }

    public function test_pricing_snapshot_is_independent_of_plan_edits(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ])->assertCreated();

        $this->plan->update(['base_price' => 19900]);

        $subscription = Subscription::where('customer_id', $this->customer->id)->firstOrFail();
        $this->assertSame(9900, $subscription->base_price);
    }

    public function test_member_cannot_create_subscription(): void
    {
        $response = $this->actingAs($this->member)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_create_duplicate_active_subscription_for_customer(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ])->assertCreated();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_cannot_create_subscription_for_inactive_customer(): void
    {
        $inactive = Customer::factory()->forMerchant($this->merchant)->inactive()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $inactive->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_cannot_create_subscription_with_archived_plan(): void
    {
        $archived = Plan::factory()->forMerchant($this->merchant)->archived()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $archived->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_customer_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_plan_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_cannot_use_customer_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $otherCustomer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_cannot_use_plan_from_another_merchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherPlan = Plan::factory()->forMerchant($otherMerchant)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $otherPlan->public_id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('plan_id');
    }

    public function test_unauthenticated_user_cannot_create_subscription(): void
    {
        $response = $this->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_internal_ids_are_not_exposed(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('merchant_id', $data);
        $this->assertArrayNotHasKey('customer_id', $data);
        $this->assertArrayNotHasKey('plan_id', $data);
        $this->assertSame(26, strlen($data['id']));
    }

    public function test_monthly_subscription_period_end_is_one_month_later(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $start = new \DateTimeImmutable($data['current_period_start']);
        $end = new \DateTimeImmutable($data['current_period_end']);
        $diff = $start->diff($end);
        $this->assertSame(1, $diff->m);
    }

    public function test_yearly_subscription_period_end_is_one_year_later(): void
    {
        $yearlyPlan = Plan::factory()->forMerchant($this->merchant)->yearly()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $yearlyPlan->public_id,
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $start = new \DateTimeImmutable($data['current_period_start']);
        $end = new \DateTimeImmutable($data['current_period_end']);
        $diff = $start->diff($end);
        $this->assertSame(1, $diff->y);
    }

    public function test_can_create_subscription_after_previous_was_cancelled(): void
    {
        Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forPlan($this->plan)
            ->cancelled()
            ->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/subscriptions', [
            'customer_id' => $this->customer->public_id,
            'plan_id' => $this->plan->public_id,
        ]);

        $response->assertCreated();
    }
}
