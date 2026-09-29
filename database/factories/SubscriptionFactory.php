<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscription>
 */
final class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $now = Carbon::now();

        return [
            'public_id' => (string) Str::ulid(),
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'started_at' => $now,
            'current_period_start' => $now,
            'current_period_end' => $now->copy()->addMonth(),
            'billing_cycle' => BillingCycle::Monthly,
            'base_price' => 49900,
            'included_usage_units' => 10000,
            'overage_rate' => 5,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => Carbon::now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => SubscriptionStatus::Expired,
        ]);
    }

    public function yearly(): static
    {
        $now = Carbon::now();

        return $this->state([
            'billing_cycle' => BillingCycle::Yearly,
            'current_period_end' => $now->copy()->addYear(),
        ]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(['merchant_id' => $merchant->id]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(['customer_id' => $customer->id]);
    }

    public function forPlan(Plan $plan): static
    {
        return $this->state([
            'plan_id' => $plan->id,
            'base_price' => $plan->base_price,
            'included_usage_units' => $plan->included_usage_units,
            'overage_rate' => $plan->overage_rate,
            'billing_cycle' => $plan->billing_cycle,
        ]);
    }
}
