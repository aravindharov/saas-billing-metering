<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
final class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = Carbon::parse('2026-01-01 00:00:00', 'UTC');
        $end = Carbon::parse('2026-02-01 00:00:00', 'UTC');

        return [
            'public_id' => (string) Str::ulid(),
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'billing_period_start' => $start,
            'billing_period_end' => $end,
            'subtotal' => 50000,
            'total' => 50000,
            'status' => InvoiceStatus::Issued,
            'issued_at' => Carbon::now(),
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(['merchant_id' => $merchant->id]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state([
            'merchant_id' => $subscription->merchant_id,
            'customer_id' => $subscription->customer_id,
            'subscription_id' => $subscription->id,
            'billing_period_start' => $subscription->current_period_start,
            'billing_period_end' => $subscription->current_period_end,
        ]);
    }
}
