<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

final readonly class UsageDropRow
{
    public function __construct(
        public string $customerPublicId,
        public string $name,
        public int $currentUsageUnits,
        public int $previousUsageUnits,
        public int $percentageChange,
    ) {}
}
