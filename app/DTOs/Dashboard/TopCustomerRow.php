<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

final readonly class TopCustomerRow
{
    public function __construct(
        public string $customerPublicId,
        public string $name,
        public string $email,
        public int $usageUnits,
    ) {}
}
