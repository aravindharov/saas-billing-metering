<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use App\Enums\BillingCycle;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use App\Services\Dashboard\ProjectedOverageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ProjectedOverageCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private ProjectedOverageCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(ProjectedOverageCalculator::class);
    }

    public function test_projected_overage_is_zero_when_usage_below_included(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 12:00:00', 'UTC'));

        $subscription = $this->activeSubscription(included: 10_000, overageRate: 500);

        DailyUsage::factory()->create([
            'merchant_id' => $subscription->merchant_id,
            'customer_id' => $subscription->customer_id,
            'usage_date' => '2026-03-10',
            'total_quantity' => 500,
        ]);

        $this->assertSame(0, $this->calculator->projectedOverageForSubscription(
            $subscription->fresh(),
            Carbon::now('UTC'),
        ));

        Carbon::setTestNow();
    }

    public function test_projected_overage_when_usage_exceeds_included_after_projection(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 12:00:00', 'UTC'));

        $subscription = $this->activeSubscription(included: 1000, overageRate: 200);

        DailyUsage::factory()->create([
            'merchant_id' => $subscription->merchant_id,
            'customer_id' => $subscription->customer_id,
            'usage_date' => '2026-03-10',
            'total_quantity' => 6000,
        ]);

        $amount = $this->calculator->projectedOverageForSubscription(
            $subscription->fresh(),
            Carbon::now('UTC'),
        );

        $this->assertGreaterThan(0, $amount);

        Carbon::setTestNow();
    }

    public function test_uses_subscription_snapshot_not_current_plan_price(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-16 12:00:00', 'UTC'));

        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create([
            'overage_rate' => 100,
            'included_usage_units' => 0,
        ]);

        $subscription = Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create([
                'included_usage_units' => 0,
                'overage_rate' => 100,
                'current_period_start' => Carbon::parse('2026-03-01 00:00:00', 'UTC'),
                'current_period_end' => Carbon::parse('2026-04-01 00:00:00', 'UTC'),
            ]);

        DailyUsage::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-05',
            'total_quantity' => 10_000,
        ]);

        $expected = $this->calculator->projectedOverageForSubscription(
            $subscription->fresh(),
            Carbon::now('UTC'),
        );

        $plan->update(['overage_rate' => 999]);

        $afterCatalogChange = $this->calculator->projectedOverageForSubscription(
            $subscription->fresh(),
            Carbon::now('UTC'),
        );

        $this->assertSame($expected, $afterCatalogChange);
        $this->assertGreaterThan(0, $expected);

        Carbon::setTestNow();
    }

    public function test_plan_change_inside_current_cycle_uses_segment_pricing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-20 12:00:00', 'UTC'));

        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $planA = Plan::factory()->forMerchant($merchant)->create();
        $planB = Plan::factory()->forMerchant($merchant)->create();

        $subscription = Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($planA)
            ->create([
                'included_usage_units' => 100,
                'overage_rate' => 1000,
                'current_period_start' => Carbon::parse('2026-03-01 00:00:00', 'UTC'),
                'current_period_end' => Carbon::parse('2026-04-01 00:00:00', 'UTC'),
            ]);

        SubscriptionPlanChange::factory()->create([
            'subscription_id' => $subscription->id,
            'from_plan_id' => $planA->id,
            'to_plan_id' => $planB->id,
            'effective_at' => Carbon::parse('2026-03-15 00:00:00', 'UTC'),
            'from_included_usage_units' => 100,
            'to_included_usage_units' => 100,
            'from_overage_rate' => 1000,
            'to_overage_rate' => 500,
            'from_billing_cycle' => BillingCycle::Monthly,
            'to_billing_cycle' => BillingCycle::Monthly,
        ]);

        DailyUsage::factory()->create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'usage_date' => '2026-03-18',
            'total_quantity' => 500,
        ]);

        $amount = $this->calculator->projectedOverageForSubscription(
            $subscription->fresh(),
            Carbon::now('UTC'),
        );

        $this->assertGreaterThan(0, $amount);

        Carbon::setTestNow();
    }

    public function test_project_usage_rounds_half_up(): void
    {
        $this->assertSame(300, $this->calculator->projectUsage(100, 10, 30));
        $this->assertSame(273, $this->calculator->projectUsage(100, 11, 30));
    }

    private function activeSubscription(int $included, int $overageRate): Subscription
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create();

        return Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create([
                'included_usage_units' => $included,
                'overage_rate' => $overageRate,
                'current_period_start' => Carbon::parse('2026-03-01 00:00:00', 'UTC'),
                'current_period_end' => Carbon::parse('2026-04-01 00:00:00', 'UTC'),
            ]);
    }
}
