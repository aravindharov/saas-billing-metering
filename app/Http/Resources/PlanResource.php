<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Plan */
final class PlanResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'name' => $this->name,
            'base_price' => $this->resource->base_price,
            'billing_cycle' => $this->resource->billing_cycle->value,
            'included_usage_units' => $this->resource->included_usage_units,
            'overage_rate' => $this->resource->overage_rate,
            'status' => $this->resource->status->value,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
