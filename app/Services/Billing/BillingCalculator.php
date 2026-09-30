<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\DTOs\Billing\BillingSegment;
use App\DTOs\Billing\InvoiceCalculation;
use App\DTOs\Billing\InvoiceLineDraft;
use App\Enums\InvoiceLineType;
use App\Models\DailyUsage;
use App\Models\Subscription;

/**
 * Computes invoice lines from pricing segments and daily usage (integer minor units only).
 *
 * Proration: round half up — (base × segment_seconds + period_seconds/2) / period_seconds
 * Overage: max(0, usage − included) × overage_rate per segment
 */
final class BillingCalculator
{
    public function __construct(
        private readonly BillingSegmentBuilder $segmentBuilder,
    ) {}

    public function calculate(Subscription $subscription): InvoiceCalculation
    {
        $periodStart = $subscription->current_period_start->copy()->utc();
        $periodEnd = $subscription->current_period_end->copy()->utc();
        $periodSeconds = max(1, (int) $periodStart->diffInSeconds($periodEnd, absolute: true));

        $segments = $this->segmentBuilder->build($subscription, $periodStart, $periodEnd);
        $lines = [];

        foreach ($segments as $segment) {
            $proratedBase = $this->prorateBase(
                $segment->pricing->basePrice,
                $segment->durationSeconds(),
                $periodSeconds,
            );

            if ($proratedBase > 0) {
                $lines[] = new InvoiceLineDraft(
                    InvoiceLineType::Base,
                    'Base subscription charge ('.$segment->label.')',
                    1,
                    $proratedBase,
                    $proratedBase,
                    [
                        'segment_start' => $segment->start->toIso8601String(),
                        'segment_end' => $segment->end->toIso8601String(),
                        'included_usage_units' => $segment->pricing->includedUsageUnits,
                    ],
                );
            }

            $segmentUsage = $this->segmentUsage(
                $subscription,
                $segment,
            );

            $billable = max(0, $segmentUsage - $segment->pricing->includedUsageUnits);
            if ($billable > 0) {
                $overageAmount = $billable * $segment->pricing->overageRate;
                $lines[] = new InvoiceLineDraft(
                    InvoiceLineType::Overage,
                    'Usage overage ('.$segment->label.')',
                    $billable,
                    $segment->pricing->overageRate,
                    $overageAmount,
                    [
                        'segment_start' => $segment->start->toIso8601String(),
                        'segment_end' => $segment->end->toIso8601String(),
                        'segment_usage' => $segmentUsage,
                        'included_usage_units' => $segment->pricing->includedUsageUnits,
                    ],
                );
            }
        }

        $subtotal = array_sum(array_map(fn (InvoiceLineDraft $l) => $l->amount, $lines));

        return new InvoiceCalculation($lines, $subtotal, $subtotal);
    }

    public function prorateBase(int $basePrice, int $segmentSeconds, int $periodSeconds): int
    {
        if ($segmentSeconds <= 0 || $basePrice <= 0) {
            return 0;
        }

        if ($segmentSeconds >= $periodSeconds) {
            return $basePrice;
        }

        $numerator = $basePrice * $segmentSeconds + intdiv($periodSeconds, 2);

        return intdiv($numerator, $periodSeconds);
    }

    private function segmentUsage(Subscription $subscription, BillingSegment $segment): int
    {
        $fromDate = $segment->start->copy()->utc()->format('Y-m-d');
        $toDate = $segment->end->copy()->utc()->format('Y-m-d');

        return (int) DailyUsage::where('merchant_id', $subscription->merchant_id)
            ->where('customer_id', $subscription->customer_id)
            ->where('usage_date', '>=', $fromDate)
            ->where('usage_date', '<', $toDate)
            ->sum('total_quantity');
    }
}
