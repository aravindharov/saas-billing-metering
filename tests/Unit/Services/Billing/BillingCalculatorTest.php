<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Billing;

use App\Enums\BillingCycle;
use App\Enums\InvoiceLineType;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use App\Services\Billing\BillingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class BillingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private BillingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(BillingCalculator::class);
    }

    public function test_assignment_example_base_plus_overage_in_minor_units(): void
    {
        $subscription = $this->subscriptionForPeriod(
            basePrice: 50000,
            included: 1000,
            overageRate: 200,
            start: '2026-03-01 00:00:00',
            end: '2026-04-01 00:00:00',
        );

        $this->dailyUsage($subscription, '2026-03-15', 1500);

        $result = $this->calculator->calculate($subscription);

        $this->assertSame(150000, $result->total);
        $this->assertCount(2, $result->lines);
        $this->assertSame(50000, $result->lines[0]->amount);
        $this->assertSame(100000, $result->lines[1]->amount);
    }

    public function test_zero_usage_charges_base_only(): void
    {
        $subscription = $this->subscriptionForPeriod(
            basePrice: 50000,
            included: 1000,
            overageRate: 200,
            start: '2026-03-01 00:00:00',
            end: '2026-04-01 00:00:00',
        );

        $result = $this->calculator->calculate($subscription);

        $this->assertSame(50000, $result->total);
        $this->assertCount(1, $result->lines);
    }

    public function test_usage_below_included_has_no_overage(): void
    {
        $subscription = $this->subscriptionForPeriod(
            basePrice: 10000,
            included: 1000,
            overageRate: 200,
            start: '2026-03-01 00:00:00',
            end: '2026-04-01 00:00:00',
        );

        $this->dailyUsage($subscription, '2026-03-10', 800);

        $result = $this->calculator->calculate($subscription);

        $this->assertSame(10000, $result->total);
    }

    public function test_usage_exactly_at_included_has_no_overage(): void
    {
        $subscription = $this->subscriptionForPeriod(
            basePrice: 10000,
            included: 1000,
            overageRate: 200,
            start: '2026-03-01 00:00:00',
            end: '2026-04-01 00:00:00',
        );

        $this->dailyUsage($subscription, '2026-03-10', 1000);

        $result = $this->calculator->calculate($subscription);

        $this->assertSame(10000, $result->total);
    }

    public function test_usage_above_included_applies_overage_rate(): void
    {
        $subscription = $this->subscriptionForPeriod(
            basePrice: 10000,
            included: 1000,
            overageRate: 200,
            start: '2026-03-01 00:00:00',
            end: '2026-04-01 00:00:00',
        );

        $this->dailyUsage($subscription, '2026-03-10', 1200);

        $result = $this->calculator->calculate($subscription);

        $this->assertSame(10000 + (200 * 200), $result->total);
    }

    public function test_proration_splits_base_across_mid_cycle_plan_change(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $planA = Plan::factory()->forMerchant($merchant)->create(['base_price' => 100000]);
        $planB = Plan::factory()->forMerchant($merchant)->create(['base_price' => 200000]);

        $start = Carbon::parse('2026-01-01 00:00:00', 'UTC');
        $end = Carbon::parse('2026-02-01 00:00:00', 'UTC');
        $changeAt = Carbon::parse('2026-01-16 00:00:00', 'UTC');

        $subscription = Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($planA)
            ->create([
                'base_price' => 100000,
                'current_period_start' => $start,
                'current_period_end' => $end,
                'started_at' => $start,
            ]);

        SubscriptionPlanChange::factory()->create([
            'subscription_id' => $subscription->id,
            'from_plan_id' => $planA->id,
            'to_plan_id' => $planB->id,
            'effective_at' => $changeAt,
            'from_base_price' => 100000,
            'to_base_price' => 200000,
            'from_included_usage_units' => $subscription->included_usage_units,
            'to_included_usage_units' => $subscription->included_usage_units,
            'from_overage_rate' => $subscription->overage_rate,
            'to_overage_rate' => $subscription->overage_rate,
            'from_billing_cycle' => BillingCycle::Monthly,
            'to_billing_cycle' => BillingCycle::Monthly,
        ]);

        $subscription->update([
            'plan_id' => $planB->id,
            'base_price' => 200000,
        ]);

        $subscription->refresh();
        $result = $this->calculator->calculate($subscription);

        $periodSeconds = (int) $start->diffInSeconds($end, absolute: true);
        $segASeconds = (int) $start->diffInSeconds($changeAt, absolute: true);
        $segBSeconds = (int) $changeAt->diffInSeconds($end, absolute: true);

        $expectedA = intdiv(100000 * $segASeconds + intdiv($periodSeconds, 2), $periodSeconds);
        $expectedB = intdiv(200000 * $segBSeconds + intdiv($periodSeconds, 2), $periodSeconds);

        $baseTotal = array_sum(array_map(
            fn ($line) => $line->amount,
            array_filter($result->lines, fn ($l) => $l->type === InvoiceLineType::Base),
        ));

        $this->assertSame($expectedA + $expectedB, $baseTotal);
    }

    public function test_overage_respects_plan_change_segments_independently(): void
    {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $planA = Plan::factory()->forMerchant($merchant)->create();
        $planB = Plan::factory()->forMerchant($merchant)->create();

        $start = Carbon::parse('2026-01-01 00:00:00', 'UTC');
        $end = Carbon::parse('2026-02-01 00:00:00', 'UTC');
        $changeAt = Carbon::parse('2026-01-15 00:00:00', 'UTC');

        $subscription = Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($planA)
            ->create([
                'base_price' => 10000,
                'included_usage_units' => 100,
                'overage_rate' => 1000,
                'current_period_start' => $start,
                'current_period_end' => $end,
                'started_at' => $start,
            ]);

        SubscriptionPlanChange::factory()->create([
            'subscription_id' => $subscription->id,
            'from_plan_id' => $planA->id,
            'to_plan_id' => $planB->id,
            'effective_at' => $changeAt,
            'from_base_price' => 10000,
            'to_base_price' => 20000,
            'from_included_usage_units' => 100,
            'to_included_usage_units' => 200,
            'from_overage_rate' => 1000,
            'to_overage_rate' => 500,
            'from_billing_cycle' => BillingCycle::Monthly,
            'to_billing_cycle' => BillingCycle::Monthly,
        ]);

        $this->dailyUsage($subscription, '2026-01-10', 150);
        $this->dailyUsage($subscription, '2026-01-20', 300);

        $subscription->refresh();
        $result = $this->calculator->calculate($subscription);

        $overageLines = array_values(array_filter(
            $result->lines,
            fn ($l) => $l->type === InvoiceLineType::Overage,
        ));

        $this->assertCount(2, $overageLines);
        $this->assertSame(50 * 1000, $overageLines[0]->amount);
        $this->assertSame(100 * 500, $overageLines[1]->amount);
    }

    public function test_prorate_base_uses_round_half_up(): void
    {
        $this->assertSame(333, $this->calculator->prorateBase(1000, 10, 30));
        $this->assertSame(367, $this->calculator->prorateBase(1000, 11, 30));
    }

    private function subscriptionForPeriod(
        int $basePrice,
        int $included,
        int $overageRate,
        string $start,
        string $end,
    ): Subscription {
        $merchant = Merchant::factory()->create();
        $customer = Customer::factory()->forMerchant($merchant)->create();
        $plan = Plan::factory()->forMerchant($merchant)->create([
            'base_price' => $basePrice,
            'included_usage_units' => $included,
            'overage_rate' => $overageRate,
        ]);

        return Subscription::factory()
            ->forMerchant($merchant)
            ->forCustomer($customer)
            ->forPlan($plan)
            ->create([
                'base_price' => $basePrice,
                'included_usage_units' => $included,
                'overage_rate' => $overageRate,
                'current_period_start' => Carbon::parse($start, 'UTC'),
                'current_period_end' => Carbon::parse($end, 'UTC'),
                'started_at' => Carbon::parse($start, 'UTC'),
            ]);
    }

    private function dailyUsage(Subscription $subscription, string $date, int $quantity): void
    {
        DailyUsage::factory()->create([
            'merchant_id' => $subscription->merchant_id,
            'customer_id' => $subscription->customer_id,
            'usage_date' => $date,
            'total_quantity' => $quantity,
        ]);
    }
}
