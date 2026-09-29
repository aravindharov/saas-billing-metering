<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class IngestUsageEventTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private User $owner;

    private User $member;

    private Customer $customer;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
        $this->owner = User::factory()->owner()->forMerchant($this->merchant)->create();
        $this->member = User::factory()->member()->forMerchant($this->merchant)->create();
        $this->customer = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $this->subscription = Subscription::factory()
            ->forMerchant($this->merchant)
            ->forCustomer($this->customer)
            ->forPlan($plan)
            ->create();
    }

    // ─── Valid ingestion ──────────────────────────────────────────

    public function test_owner_can_ingest_usage_event(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_10001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 25,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.event_id', 'evt_10001');
        $response->assertJsonPath('data.quantity', 25);
        $response->assertJsonStructure(['data' => [
            'id', 'event_id', 'customer', 'subscription',
            'quantity', 'occurred_at', 'created_at',
        ]]);

        $this->assertDatabaseHas('usage_events', [
            'merchant_id' => $this->merchant->id,
            'event_id' => 'evt_10001',
            'quantity' => 25,
        ]);
    }

    public function test_member_can_ingest_usage_event(): void
    {
        $response = $this->actingAs($this->member)->postJson('/api/v1/usage', [
            'event_id' => 'evt_20001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertCreated();
    }

    public function test_occurred_at_is_stored_in_utc(): void
    {
        $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_utc_test',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 1,
            'occurred_at' => '2026-09-29T20:00:00+05:30',
        ])->assertCreated();

        $event = UsageEvent::where('event_id', 'evt_utc_test')->firstOrFail();
        $this->assertSame('2026-09-29T14:30:00+00:00', $event->occurred_at->toIso8601String());
    }

    public function test_internal_ids_are_not_exposed(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_hidden',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 5,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertCreated();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('merchant_id', $data);
        $this->assertArrayNotHasKey('customer_id', $data);
        $this->assertArrayNotHasKey('subscription_id', $data);
        $this->assertSame(26, strlen($data['id']));
    }

    // ─── Validation ───────────────────────────────────────────────

    public function test_event_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('event_id');
    }

    public function test_customer_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_subscription_id_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('subscription_id');
    }

    public function test_quantity_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_quantity_must_be_positive(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 0,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_negative_quantity_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => -5,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('quantity');
    }

    public function test_occurred_at_is_required(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('occurred_at');
    }

    public function test_invalid_occurred_at_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_001',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => 'not-a-date',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('occurred_at');
    }

    public function test_event_id_max_length(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => str_repeat('x', 256),
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('event_id');
    }

    // ─── Customer/Subscription validation ─────────────────────────

    public function test_inactive_customer_is_rejected(): void
    {
        $inactive = Customer::factory()->forMerchant($this->merchant)->inactive()->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $sub = Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($inactive)->forPlan($plan)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_inactive_cust',
            'customer_id' => $inactive->public_id,
            'subscription_id' => $sub->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_cancelled_subscription_is_rejected(): void
    {
        $customer2 = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $cancelled = Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customer2)->forPlan($plan)->cancelled()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_cancelled_sub',
            'customer_id' => $customer2->public_id,
            'subscription_id' => $cancelled->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('subscription_id');
    }

    public function test_subscription_must_belong_to_customer(): void
    {
        $other = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $otherSub = Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($other)->forPlan($plan)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_wrong_cust',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $otherSub->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('subscription_id');
    }

    public function test_nonexistent_customer_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_bad_cust',
            'customer_id' => '01NONEXISTENT000000000000',
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_nonexistent_subscription_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_bad_sub',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => '01NONEXISTENT000000000000',
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('subscription_id');
    }

    // ─── Future-dated events ──────────────────────────────────────

    public function test_far_future_event_is_rejected(): void
    {
        $future = Carbon::now()->addHour()->toIso8601String();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_future',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => $future,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('occurred_at');
    }

    public function test_small_clock_skew_is_accepted(): void
    {
        $slightFuture = Carbon::now()->addMinutes(3)->toIso8601String();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_clock_skew',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => $slightFuture,
        ]);

        $response->assertCreated();
    }

    // ─── Late events ──────────────────────────────────────────────

    public function test_late_historical_event_is_accepted(): void
    {
        $pastDate = Carbon::now()->subDays(5)->toIso8601String();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_late',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => $pastDate,
        ]);

        $response->assertCreated();
    }

    // ─── Tenant isolation ─────────────────────────────────────────

    public function test_cross_tenant_customer_is_rejected(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_cross_cust',
            'customer_id' => $otherCustomer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('customer_id');
    }

    public function test_cross_tenant_subscription_is_rejected(): void
    {
        $other = Merchant::factory()->create();
        $otherCustomer = Customer::factory()->forMerchant($other)->create();
        $otherPlan = Plan::factory()->forMerchant($other)->create();
        $otherSub = Subscription::factory()->forMerchant($other)
            ->forCustomer($otherCustomer)->forPlan($otherPlan)->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_cross_sub',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $otherSub->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('subscription_id');
    }

    public function test_merchant_id_cannot_be_injected(): void
    {
        $other = Merchant::factory()->create();

        $response = $this->actingAs($this->owner)->postJson('/api/v1/usage', [
            'event_id' => 'evt_inject',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
            'merchant_id' => $other->id,
        ]);

        $response->assertCreated();
        $event = UsageEvent::where('event_id', 'evt_inject')->firstOrFail();
        $this->assertEquals($this->merchant->id, $event->merchant_id);
    }

    // ─── Authentication ───────────────────────────────────────────

    public function test_unauthenticated_user_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/usage', [
            'event_id' => 'evt_unauth',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ]);

        $response->assertUnauthorized();
    }
}
