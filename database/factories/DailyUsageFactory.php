<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<DailyUsage>
 */
final class DailyUsageFactory extends Factory
{
    protected $model = DailyUsage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'usage_date' => Carbon::today()->toDateString(),
            'total_quantity' => $this->faker->numberBetween(1, 10000),
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

    public function forDate(string $date): static
    {
        return $this->state(['usage_date' => $date]);
    }
}
