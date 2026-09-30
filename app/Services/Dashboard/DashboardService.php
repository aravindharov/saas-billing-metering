<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardSnapshot;
use App\DTOs\Dashboard\TopCustomerRow;
use App\DTOs\Dashboard\UsageDropRow;
use App\Enums\CustomerStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function __construct(
        private readonly ProjectedOverageCalculator $projectedOverageCalculator,
    ) {}

    public function forMerchant(Merchant $merchant, ?Carbon $asOf = null): DashboardSnapshot
    {
        $now = ($asOf ?? Carbon::now())->copy()->utc();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $topCustomers = $this->topCustomersByUsage($merchant, $monthStart, $today);
        $usageDrops = $this->usageDrops($merchant, $now);
        $projectedOverage = $this->totalProjectedOverage($merchant, $now);

        $activeSubscriptions = Subscription::query()
            ->where('merchant_id', $merchant->id)
            ->where('status', SubscriptionStatus::Active)
            ->where('current_period_start', '<=', $now)
            ->where('current_period_end', '>', $now)
            ->get(['current_period_start', 'current_period_end']);

        $cycleStart = $activeSubscriptions->min('current_period_start');
        $cycleEnd = $activeSubscriptions->max('current_period_end');

        $currentMonthUsage = (int) DailyUsage::query()
            ->where('merchant_id', $merchant->id)
            ->where('usage_date', '>=', $monthStart->toDateString())
            ->where('usage_date', '<=', $today)
            ->sum('total_quantity');

        $activeCustomers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->where('status', CustomerStatus::Active)
            ->count();

        return new DashboardSnapshot(
            generatedAt: Carbon::now()->utc(),
            monthStart: $monthStart,
            monthEnd: $monthEnd,
            currentMonthUsageUnits: $currentMonthUsage,
            activeCustomerCount: $activeCustomers,
            activeSubscriptionCount: $activeSubscriptions->count(),
            billingCycleStart: $cycleStart ? Carbon::parse($cycleStart)->utc() : null,
            billingCycleEnd: $cycleEnd ? Carbon::parse($cycleEnd)->utc() : null,
            topCustomers: $topCustomers,
            projectedOverageAmount: $projectedOverage,
            usageDrops: $usageDrops,
        );
    }

    /**
     * @param  string  $toDate  inclusive UTC date (Y-m-d)
     * @return list<TopCustomerRow>
     */
    private function topCustomersByUsage(Merchant $merchant, Carbon $from, string $toDate): array
    {
        $rows = DailyUsage::query()
            ->select('customer_id', DB::raw('SUM(total_quantity) as usage_total'))
            ->where('merchant_id', $merchant->id)
            ->where('usage_date', '>=', $from->toDateString())
            ->where('usage_date', '<=', $toDate)
            ->groupBy('customer_id')
            ->orderByDesc('usage_total')
            ->limit(5)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $customers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->whereIn('id', $rows->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            /** @var int $customerId */
            $customerId = (int) $row->customer_id;
            /** @var int|string $usageTotalRaw */
            $usageTotalRaw = $row->getAttributes()['usage_total'] ?? 0;

            $customer = $customers->get($customerId);
            if ($customer === null) {
                continue;
            }

            $result[] = new TopCustomerRow(
                $customer->public_id,
                $customer->name,
                $customer->email,
                (int) $usageTotalRaw,
            );
        }

        return $result;
    }

    /**
     * @return list<UsageDropRow>
     */
    private function usageDrops(Merchant $merchant, Carbon $now): array
    {
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->startOfDay();

        $previousStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $compareDay = min($now->day, $previousStart->daysInMonth);
        $previousEnd = $previousStart->copy()->day($compareDay);

        $currentTotals = $this->usageTotalsByCustomer($merchant, $currentStart, $currentEnd);
        $previousTotals = $this->usageTotalsByCustomer($merchant, $previousStart, $previousEnd);

        $customerIds = $previousTotals->keys()->merge($currentTotals->keys())->unique();
        if ($customerIds->isEmpty()) {
            return [];
        }

        $customers = Customer::query()
            ->where('merchant_id', $merchant->id)
            ->whereIn('id', $customerIds)
            ->get()
            ->keyBy('id');

        $drops = [];

        foreach ($customerIds as $customerId) {
            $customerId = (int) $customerId;
            $previous = (int) ($previousTotals->get($customerId, 0));
            $current = (int) ($currentTotals->get($customerId, 0));

            if ($previous <= 0) {
                continue;
            }

            // Strict > 50% drop: current must be less than half of previous (exact 50% excluded).
            if ($current * 2 >= $previous) {
                continue;
            }

            $customer = $customers->get($customerId);
            if ($customer === null) {
                continue;
            }

            $percentageChange = intdiv(($current - $previous) * 100, $previous);

            $drops[] = new UsageDropRow(
                $customer->public_id,
                $customer->name,
                $current,
                $previous,
                $percentageChange,
            );
        }

        usort($drops, fn (UsageDropRow $a, UsageDropRow $b) => $a->percentageChange <=> $b->percentageChange);

        return $drops;
    }

    /** @return Collection<int, int> */
    private function usageTotalsByCustomer(Merchant $merchant, Carbon $from, Carbon $to): Collection
    {
        return DailyUsage::query()
            ->select('customer_id', DB::raw('SUM(total_quantity) as usage_total'))
            ->where('merchant_id', $merchant->id)
            ->where('usage_date', '>=', $from->toDateString())
            ->where('usage_date', '<=', $to->toDateString())
            ->groupBy('customer_id')
            ->pluck('usage_total', 'customer_id')
            ->map(fn ($v) => (int) $v);
    }

    private function totalProjectedOverage(Merchant $merchant, Carbon $now): int
    {
        $subscriptions = Subscription::query()
            ->where('merchant_id', $merchant->id)
            ->where('status', SubscriptionStatus::Active)
            ->where('current_period_start', '<=', $now)
            ->where('current_period_end', '>', $now)
            ->with('planChanges')
            ->get();

        $total = 0;
        foreach ($subscriptions as $subscription) {
            $total += $this->projectedOverageCalculator->projectedOverageForSubscription($subscription, $now);
        }

        return $total;
    }
}
