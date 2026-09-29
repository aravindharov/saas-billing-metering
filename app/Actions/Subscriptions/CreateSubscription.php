<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateSubscription
{
    /**
     * Create a subscription with a pricing snapshot from the plan.
     *
     * Uses a database transaction and a row-level lock on the customer
     * to prevent concurrent duplicate-active-subscription creation.
     */
    public function execute(Merchant $merchant, Customer $customer, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($merchant, $customer, $plan): Subscription {
            // Lock the customer row to prevent concurrent subscription creation.
            Customer::where('id', $customer->id)->lockForUpdate()->first();

            if (! $customer->isActive()) {
                throw ValidationException::withMessages([
                    'customer_id' => ['The customer is not active.'],
                ]);
            }

            if (! $plan->isActive()) {
                throw ValidationException::withMessages([
                    'plan_id' => ['The plan is not active.'],
                ]);
            }

            $activeExists = Subscription::where('customer_id', $customer->id)
                ->where('status', SubscriptionStatus::Active)
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'customer_id' => ['The customer already has an active subscription.'],
                ]);
            }

            $now = Carbon::now();
            $periodEnd = $this->calculatePeriodEnd($now, $plan->billing_cycle);

            $subscription = new Subscription;
            $subscription->public_id = '';
            $subscription->merchant_id = $merchant->id;
            $subscription->customer_id = $customer->id;
            $subscription->plan_id = $plan->id;
            $subscription->status = SubscriptionStatus::Active;
            $subscription->started_at = $now;
            $subscription->current_period_start = $now;
            $subscription->current_period_end = $periodEnd;
            $subscription->billing_cycle = $plan->billing_cycle;
            $subscription->base_price = $plan->base_price;
            $subscription->included_usage_units = $plan->included_usage_units;
            $subscription->overage_rate = $plan->overage_rate;
            $subscription->save();

            return $subscription->refresh();
        });
    }

    private function calculatePeriodEnd(Carbon $start, BillingCycle $cycle): Carbon
    {
        return match ($cycle) {
            BillingCycle::Monthly => $start->copy()->addMonth(),
            BillingCycle::Yearly => $start->copy()->addYear(),
        };
    }
}
