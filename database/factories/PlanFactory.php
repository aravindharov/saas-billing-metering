<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\PlanStatus;
use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
final class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'merchant_id' => Merchant::factory(),
            'name' => fake()->unique()->words(2, true),
            'base_price' => fake()->numberBetween(1000, 100000),
            'billing_cycle' => BillingCycle::Monthly,
            'included_usage_units' => fake()->numberBetween(100, 100000),
            'overage_rate' => fake()->numberBetween(1, 100),
            'status' => PlanStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(['status' => PlanStatus::Archived]);
    }

    public function monthly(): static
    {
        return $this->state(['billing_cycle' => BillingCycle::Monthly]);
    }

    public function yearly(): static
    {
        return $this->state(['billing_cycle' => BillingCycle::Yearly]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(['merchant_id' => $merchant->id]);
    }
}
