<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SubscriptionPlanChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPlanChange */
final class SubscriptionPlanChangeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'from_plan' => $this->fromPlan ? ['id' => $this->fromPlan->public_id, 'name' => $this->fromPlan->name] : null,
            'to_plan' => $this->toPlan ? ['id' => $this->toPlan->public_id, 'name' => $this->toPlan->name] : null,
            'effective_at' => $this->resource->effective_at?->toIso8601String(),
            'from_base_price' => $this->resource->from_base_price,
            'from_included_usage_units' => $this->resource->from_included_usage_units,
            'from_overage_rate' => $this->resource->from_overage_rate,
            'from_billing_cycle' => $this->resource->from_billing_cycle->value,
            'to_base_price' => $this->resource->to_base_price,
            'to_included_usage_units' => $this->resource->to_included_usage_units,
            'to_overage_rate' => $this->resource->to_overage_rate,
            'to_billing_cycle' => $this->resource->to_billing_cycle->value,
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
