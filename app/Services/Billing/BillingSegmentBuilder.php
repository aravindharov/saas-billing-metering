<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\DTOs\Billing\BillingSegment;
use App\DTOs\Billing\PricingSnapshot;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds time-bounded pricing segments for a billing period using plan-change history.
 */
final class BillingSegmentBuilder
{
    /**
     * @return list<BillingSegment>
     */
    public function build(Subscription $subscription, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodStart = $periodStart->copy()->utc();
        $periodEnd = $periodEnd->copy()->utc();

        /** @var Collection<int, SubscriptionPlanChange> $changes */
        $changes = $subscription->planChanges()
            ->where('effective_at', '<', $periodEnd)
            ->orderBy('effective_at')
            ->get();

        $boundaries = [$periodStart];
        foreach ($changes as $change) {
            $at = $change->effective_at->copy()->utc();
            if ($at->greaterThan($periodStart) && $at->lessThan($periodEnd)) {
                $boundaries[] = $at;
            }
        }
        $boundaries[] = $periodEnd;

        $segments = [];
        for ($i = 0; $i < count($boundaries) - 1; $i++) {
            $start = $boundaries[$i];
            $end = $boundaries[$i + 1];
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $pricing = $this->pricingAt($subscription, $start, $changes);
            $segments[] = new BillingSegment(
                $start,
                $end,
                $pricing,
                sprintf('%s – %s', $start->toDateString(), $end->toDateString()),
            );
        }

        return $segments;
    }

    /**
     * @param  Collection<int, SubscriptionPlanChange>  $changes
     */
    private function pricingAt(
        Subscription $subscription,
        Carbon $instant,
        Collection $changes,
    ): PricingSnapshot {
        $instant = $instant->copy()->utc();

        $latestAtOrBefore = $changes
            ->filter(fn (SubscriptionPlanChange $c) => $c->effective_at->lte($instant))
            ->last();

        if ($latestAtOrBefore !== null) {
            return new PricingSnapshot(
                $latestAtOrBefore->to_base_price,
                $latestAtOrBefore->to_included_usage_units,
                $latestAtOrBefore->to_overage_rate,
            );
        }

        $firstAfter = $changes
            ->first(fn (SubscriptionPlanChange $c) => $c->effective_at->gt($instant));

        if ($firstAfter !== null) {
            return new PricingSnapshot(
                $firstAfter->from_base_price,
                $firstAfter->from_included_usage_units,
                $firstAfter->from_overage_rate,
            );
        }

        return new PricingSnapshot(
            $subscription->base_price,
            $subscription->included_usage_units,
            $subscription->overage_rate,
        );
    }
}
