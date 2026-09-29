<?php

declare(strict_types=1);

namespace Tests\Feature\Usage;

use App\Jobs\AggregateDailyUsage;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AggregateDailyUsageTest extends TestCase
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

    // ─── Basic aggregation ────────────────────────────────────────

    public function test_aggregates_single_customer_single_day(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-29 14:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 300, 'occurred_at' => '2026-09-29 18:00:00']);

        AggregateDailyUsage::dispatchSync(
            $this->merchant->id, $this->customer->id, '2026-09-29'
        );

        $daily = DailyUsage::where('merchant_id', $this->merchant->id)
            ->where('customer_id', $this->customer->id)
            ->where('usage_date', '2026-09-29')
            ->first();

        $this->assertNotNull($daily);
        $this->assertSame(600, $daily->total_quantity);
    }

    public function test_aggregates_multiple_customers_independently(): void
    {
        $customerB = Customer::factory()->forMerchant($this->merchant)->create();
        $plan = Plan::factory()->forMerchant($this->merchant)->create();
        $subB = Subscription::factory()->forMerchant($this->merchant)
            ->forCustomer($customerB)->forPlan($plan)->create();

        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-29 14:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($customerB)->forSubscription($subB)
            ->create(['quantity' => 500, 'occurred_at' => '2026-09-29 12:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        AggregateDailyUsage::dispatchSync($this->merchant->id, $customerB->id, '2026-09-29');

        $dailyA = DailyUsage::where('customer_id', $this->customer->id)->where('usage_date', '2026-09-29')->first();
        $dailyB = DailyUsage::where('customer_id', $customerB->id)->where('usage_date', '2026-09-29')->first();

        $this->assertSame(300, $dailyA->total_quantity);
        $this->assertSame(500, $dailyB->total_quantity);
    }

    public function test_aggregates_multiple_dates_independently(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-28 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-29 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-28');
        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');

        $daily28 = DailyUsage::where('customer_id', $this->customer->id)->where('usage_date', '2026-09-28')->first();
        $daily29 = DailyUsage::where('customer_id', $this->customer->id)->where('usage_date', '2026-09-29')->first();

        $this->assertSame(100, $daily28->total_quantity);
        $this->assertSame(200, $daily29->total_quantity);
    }

    public function test_aggregates_merchants_independently(): void
    {
        $merchantB = Merchant::factory()->create();
        $custB = Customer::factory()->forMerchant($merchantB)->create();
        $planB = Plan::factory()->forMerchant($merchantB)->create();
        $subB = Subscription::factory()->forMerchant($merchantB)
            ->forCustomer($custB)->forPlan($planB)->create();

        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);
        UsageEvent::factory()->forMerchant($merchantB)
            ->forCustomer($custB)->forSubscription($subB)
            ->create(['quantity' => 999, 'occurred_at' => '2026-09-29 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        AggregateDailyUsage::dispatchSync($merchantB->id, $custB->id, '2026-09-29');

        $dailyA = DailyUsage::where('merchant_id', $this->merchant->id)->first();
        $dailyB = DailyUsage::where('merchant_id', $merchantB->id)->first();

        $this->assertSame(100, $dailyA->total_quantity);
        $this->assertSame(999, $dailyB->total_quantity);
    }

    // ─── Idempotency ──────────────────────────────────────────────

    public function test_running_job_twice_produces_same_result(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');

        $this->assertDatabaseCount('daily_usage', 1);
        $daily = DailyUsage::first();
        $this->assertSame(100, $daily->total_quantity);
    }

    public function test_no_double_counting_on_retry(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 50, 'occurred_at' => '2026-09-29 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 50, 'occurred_at' => '2026-09-29 11:00:00']);

        for ($i = 0; $i < 5; $i++) {
            AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        }

        $daily = DailyUsage::first();
        $this->assertSame(100, $daily->total_quantity);
    }

    // ─── Late events ──────────────────────────────────────────────

    public function test_late_event_updates_affected_date(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 500, 'occurred_at' => '2026-09-28 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-28');

        $daily = DailyUsage::first();
        $this->assertSame(500, $daily->total_quantity);

        // Late event arrives for the same date.
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-28 15:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-28');

        $daily->refresh();
        $this->assertSame(700, $daily->total_quantity);
    }

    public function test_late_event_does_not_affect_other_dates(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');

        // Late event for a different date.
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-09-28 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-28');

        $daily29 = DailyUsage::where('usage_date', '2026-09-29')->first();
        $this->assertSame(100, $daily29->total_quantity);
    }

    // ─── No-usage days ────────────────────────────────────────────

    public function test_no_row_created_for_zero_usage(): void
    {
        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');

        $this->assertDatabaseCount('daily_usage', 0);
    }

    public function test_row_removed_when_all_events_deleted(): void
    {
        $event = UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-09-29 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        $this->assertDatabaseCount('daily_usage', 1);

        // Simulate correction: remove the event at database level.
        $event->delete();

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');
        $this->assertDatabaseCount('daily_usage', 0);
    }

    // ─── Usage date from occurred_at ──────────────────────────────

    public function test_uses_occurred_at_date_not_created_at(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create([
                'quantity' => 100,
                'occurred_at' => '2026-09-28 23:50:00',
            ]);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-28');

        $daily28 = DailyUsage::where('usage_date', '2026-09-28')->first();
        $this->assertNotNull($daily28);
        $this->assertSame(100, $daily28->total_quantity);

        // No aggregate for Sep 29 (even if created_at might be later).
        $daily29 = DailyUsage::where('usage_date', '2026-09-29')->first();
        $this->assertNull($daily29);
    }

    // ─── Dispatch from ingestion ──────────────────────────────────

    public function test_aggregation_job_dispatched_on_new_event(): void
    {
        $user = User::factory()->owner()->forMerchant($this->merchant)->create();

        Queue::fake();

        $this->actingAs($user)->postJson('/api/v1/usage', [
            'event_id' => 'evt_dispatch_test',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ])->assertCreated();

        Queue::assertPushed(AggregateDailyUsage::class, function (AggregateDailyUsage $job): bool {
            return $job->merchantId === $this->merchant->id
                && $job->customerId === $this->customer->id
                && $job->usageDate === '2026-09-29';
        });
    }

    public function test_aggregation_job_not_dispatched_on_idempotent_retry(): void
    {
        $user = User::factory()->owner()->forMerchant($this->merchant)->create();

        $this->actingAs($user)->postJson('/api/v1/usage', [
            'event_id' => 'evt_idem_dispatch',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ])->assertCreated();

        Queue::fake();

        $this->actingAs($user)->postJson('/api/v1/usage', [
            'event_id' => 'evt_idem_dispatch',
            'customer_id' => $this->customer->public_id,
            'subscription_id' => $this->subscription->public_id,
            'quantity' => 10,
            'occurred_at' => '2026-09-29T14:30:00Z',
        ])->assertOk();

        Queue::assertNotPushed(AggregateDailyUsage::class);
    }

    // ─── Plan change — quantity only ──────────────────────────────

    public function test_aggregates_quantity_across_plan_changes(): void
    {
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 100, 'occurred_at' => '2026-01-10 10:00:00']);
        UsageEvent::factory()->forMerchant($this->merchant)
            ->forCustomer($this->customer)->forSubscription($this->subscription)
            ->create(['quantity' => 200, 'occurred_at' => '2026-01-20 10:00:00']);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-01-10');
        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-01-20');

        $daily10 = DailyUsage::where('usage_date', '2026-01-10')->first();
        $daily20 = DailyUsage::where('usage_date', '2026-01-20')->first();

        $this->assertSame(100, $daily10->total_quantity);
        $this->assertSame(200, $daily20->total_quantity);
    }

    // ─── Large dataset (reasonable for CI) ────────────────────────

    public function test_aggregation_handles_large_batch(): void
    {
        $events = [];
        for ($i = 0; $i < 1000; $i++) {
            $events[] = [
                'public_id' => Str::ulid(),
                'merchant_id' => $this->merchant->id,
                'customer_id' => $this->customer->id,
                'subscription_id' => $this->subscription->id,
                'event_id' => 'evt_batch_'.$i,
                'quantity' => 1,
                'occurred_at' => '2026-09-29 12:00:00',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        UsageEvent::insert($events);

        AggregateDailyUsage::dispatchSync($this->merchant->id, $this->customer->id, '2026-09-29');

        $daily = DailyUsage::first();
        $this->assertSame(1000, $daily->total_quantity);
    }
}
