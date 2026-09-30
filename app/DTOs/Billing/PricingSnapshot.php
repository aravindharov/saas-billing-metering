<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

final readonly class PricingSnapshot
{
    public function __construct(
        public int $basePrice,
        public int $includedUsageUnits,
        public int $overageRate,
    ) {}
}
