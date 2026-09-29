<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DailyUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DailyUsage */
final class DailyUsageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'name' => $this->customer->name,
            ]),
            'usage_date' => $this->resource->usage_date?->format('Y-m-d'),
            'total_quantity' => $this->resource->total_quantity,
        ];
    }
}
