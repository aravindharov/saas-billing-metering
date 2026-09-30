<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use Illuminate\Support\Carbon;

final readonly class DashboardSnapshot
{
    /**
     * @param  list<TopCustomerRow>  $topCustomers
     * @param  list<UsageDropRow>  $usageDrops
     */
    public function __construct(
        public Carbon $generatedAt,
        public Carbon $monthStart,
        public Carbon $monthEnd,
        public int $currentMonthUsageUnits,
        public int $activeCustomerCount,
        public int $activeSubscriptionCount,
        public ?Carbon $billingCycleStart,
        public ?Carbon $billingCycleEnd,
        public array $topCustomers,
        public int $projectedOverageAmount,
        public array $usageDrops,
    ) {}
}
