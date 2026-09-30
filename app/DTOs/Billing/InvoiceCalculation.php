<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

final readonly class InvoiceCalculation
{
    /** @param list<InvoiceLineDraft> $lines */
    public function __construct(
        public array $lines,
        public int $subtotal,
        public int $total,
    ) {}
}
