<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
final class SubscriptionResource extends JsonResource
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
            'plan' => $this->whenLoaded('plan', fn () => [
                'id' => $this->plan->public_id,
                'name' => $this->plan->name,
            ]),
            'status' => $this->resource->status->value,
            'billing_cycle' => $this->resource->billing_cycle->value,
            'base_price' => $this->resource->base_price,
            'included_usage_units' => $this->resource->included_usage_units,
            'overage_rate' => $this->resource->overage_rate,
            'started_at' => $this->resource->started_at?->toIso8601String(),
            'current_period_start' => $this->resource->current_period_start?->toIso8601String(),
            'current_period_end' => $this->resource->current_period_end?->toIso8601String(),
            'cancelled_at' => $this->resource->cancelled_at?->toIso8601String(),
            'plan_changes' => SubscriptionPlanChangeResource::collection(
                $this->whenLoaded('planChanges')
            ),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
