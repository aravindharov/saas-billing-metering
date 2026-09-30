<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InvoiceLine */
final class InvoiceLineResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource->type->value,
            'description' => $this->resource->description,
            'quantity' => $this->resource->quantity,
            'unit_price' => $this->resource->unit_price,
            'amount' => $this->resource->amount,
            'metadata' => $this->resource->metadata,
        ];
    }
}
