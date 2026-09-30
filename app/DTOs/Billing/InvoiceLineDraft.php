<?php

declare(strict_types=1);

namespace App\DTOs\Billing;

use App\Enums\InvoiceLineType;

final readonly class InvoiceLineDraft
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public InvoiceLineType $type,
        public string $description,
        public int $quantity,
        public int $unitPrice,
        public int $amount,
        public ?array $metadata = null,
    ) {}
}
