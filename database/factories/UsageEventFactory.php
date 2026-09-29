<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<UsageEvent>
 */
final class UsageEventFactory extends Factory
{
    protected $model = UsageEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'event_id' => 'evt_'.$this->faker->unique()->uuid(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'occurred_at' => Carbon::now(),
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(['merchant_id' => $merchant->id]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(['customer_id' => $customer->id]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(['subscription_id' => $subscription->id]);
    }

    public function historical(int $daysAgo = 3): static
    {
        return $this->state([
            'occurred_at' => Carbon::now()->subDays($daysAgo),
        ]);
    }

    public function withEventId(string $eventId): static
    {
        return $this->state(['event_id' => $eventId]);
    }
}
