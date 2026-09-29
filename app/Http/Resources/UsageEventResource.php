<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UsageEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UsageEvent */
final class UsageEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'event_id' => $this->event_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->public_id,
                'name' => $this->customer->name,
            ]),
            'subscription' => $this->whenLoaded('subscription', fn () => [
                'id' => $this->subscription->public_id,
            ]),
            'quantity' => $this->resource->quantity,
            'occurred_at' => $this->resource->occurred_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
