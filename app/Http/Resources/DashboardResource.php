<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\Dashboard\DashboardSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DashboardSnapshot */
final class DashboardResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var DashboardSnapshot $snapshot */
        $snapshot = $this->resource;

        return [
            'generated_at' => $snapshot->generatedAt->toIso8601String(),
            'period' => [
                'month_start' => $snapshot->monthStart->toDateString(),
                'month_end' => $snapshot->monthEnd->toDateString(),
            ],
            'summary' => [
                'current_month_usage_units' => $snapshot->currentMonthUsageUnits,
                'active_customers' => $snapshot->activeCustomerCount,
                'active_subscriptions' => $snapshot->activeSubscriptionCount,
            ],
            'billing_cycle' => [
                'period_start' => $snapshot->billingCycleStart?->toIso8601String(),
                'period_end' => $snapshot->billingCycleEnd?->toIso8601String(),
            ],
            'top_customers' => array_map(fn ($row) => [
                'customer_id' => $row->customerPublicId,
                'name' => $row->name,
                'email' => $row->email,
                'usage_units' => $row->usageUnits,
            ], $snapshot->topCustomers),
            'projected_overage_revenue' => [
                'amount' => $snapshot->projectedOverageAmount,
                'currency' => 'INR',
                'unit' => 'paise',
            ],
            'usage_drops' => array_map(fn ($row) => [
                'customer_id' => $row->customerPublicId,
                'name' => $row->name,
                'current_usage_units' => $row->currentUsageUnits,
                'previous_usage_units' => $row->previousUsageUnits,
                'percentage_change' => $row->percentageChange,
            ], $snapshot->usageDrops),
        ];
    }
}
