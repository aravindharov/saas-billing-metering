<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
final class InvoiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'name' => $this->customer->name,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription->public_id,
            ]),
            'billing_period_start' => $this->resource->billing_period_start?->toIso8601String(),
            'billing_period_end' => $this->resource->billing_period_end?->toIso8601String(),
            'subtotal' => $this->resource->subtotal,
            'total' => $this->resource->total,
            'status' => $this->resource->status->value,
            'issued_at' => $this->resource->issued_at?->toIso8601String(),
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
