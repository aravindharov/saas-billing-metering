<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

use Illuminate\Support\Carbon;

final readonly class BillingSegment
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public PricingSnapshot $pricing,
        public string $label,
    ) {}

    public function durationSeconds(): int
    {
        return max(0, (int) $this->start->diffInSeconds($this->end, absolute: true));
    }
}
