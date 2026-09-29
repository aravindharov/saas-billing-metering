<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlanChange>
 */
final class SubscriptionPlanChangeFactory extends Factory
{
    protected $model = SubscriptionPlanChange::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'subscription_id' => Subscription::factory(),
            'from_plan_id' => Plan::factory(),
            'to_plan_id' => Plan::factory(),
            'effective_at' => Carbon::now(),
            'from_base_price' => 49900,
            'from_included_usage_units' => 10000,
            'from_overage_rate' => 5,
            'from_billing_cycle' => BillingCycle::Monthly,
            'to_base_price' => 99900,
            'to_included_usage_units' => 50000,
            'to_overage_rate' => 3,
            'to_billing_cycle' => BillingCycle::Monthly,
        ];
    }
}
