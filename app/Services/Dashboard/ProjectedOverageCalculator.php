<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\DTOs\Billing\BillingSegment;
use App\Models\DailyUsage;
use App\Models\Subscription;
use App\Services\Billing\BillingSegmentBuilder;
use Illuminate\Support\Carbon;

/**
 * Estimates end-of-cycle overage revenue from usage so far (integer paise, per pricing segment).
 */
final class ProjectedOverageCalculator
{
    public function __construct(
        private readonly BillingSegmentBuilder $segmentBuilder,
    ) {}

    public function projectedOverageForSubscription(Subscription $subscription, Carbon $asOf): int
    {
        $asOf = $asOf->copy()->utc();
        $periodStart = $subscription->current_period_start->copy()->utc();
        $periodEnd = $subscription->current_period_end->copy()->utc();

        if ($asOf->lt($periodStart) || $asOf->gte($periodEnd)) {
            return 0;
        }

        $subscription->loadMissing('planChanges');

        $segments = $this->segmentBuilder->build($subscription, $periodStart, $periodEnd);
        $total = 0;

        foreach ($segments as $segment) {
            $total += $this->projectedOverageForSegment($subscription, $segment, $asOf);
        }

        return $total;
    }

    public function projectUsage(int $usageSoFar, int $elapsedSeconds, int $totalSeconds): int
    {
        if ($usageSoFar <= 0 || $elapsedSeconds <= 0) {
            return 0;
        }

        if ($elapsedSeconds >= $totalSeconds) {
            return $usageSoFar;
        }

        $numerator = $usageSoFar * $totalSeconds + intdiv($elapsedSeconds, 2);

        return intdiv($numerator, $elapsedSeconds);
    }

    private function projectedOverageForSegment(
        Subscription $subscription,
        BillingSegment $segment,
        Carbon $asOf,
    ): int {
        $segmentStart = $segment->start->copy()->utc();
        $segmentEnd = $segment->end->copy()->utc();
        $effectiveEnd = $asOf->lt($segmentEnd) ? $asOf : $segmentEnd;

        if ($effectiveEnd->lte($segmentStart)) {
            return 0;
        }

        $usageSoFar = $this->usageBetween($subscription, $segmentStart, $effectiveEnd);
        $segmentSeconds = max(1, $segment->durationSeconds());
        $elapsedSeconds = max(0, (int) $segmentStart->diffInSeconds($effectiveEnd, absolute: true));

        if ($elapsedSeconds >= $segmentSeconds) {
            $projectedUsage = $usageSoFar;
        } else {
            $projectedUsage = $this->projectUsage($usageSoFar, $elapsedSeconds, $segmentSeconds);
        }

        $billable = max(0, $projectedUsage - $segment->pricing->includedUsageUnits);

        return $billable * $segment->pricing->overageRate;
    }

    private function usageBetween(Subscription $subscription, Carbon $from, Carbon $to): int
    {
        $fromDate = $from->format('Y-m-d');
        $toDate = $to->format('Y-m-d');

        return (int) DailyUsage::query()
            ->where('merchant_id', $subscription->merchant_id)
            ->where('customer_id', $subscription->customer_id)
            ->where('usage_date', '>=', $fromDate)
            ->where('usage_date', '<=', $toDate)
            ->sum('total_quantity');
    }
}
