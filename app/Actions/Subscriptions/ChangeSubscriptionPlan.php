<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlanChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeSubscriptionPlan
{
    /**
     * Change an active subscription's plan.
     *
     * Creates a plan-change history record and updates the subscription's
     * current plan and pricing snapshot. Uses a transaction with a row
     * lock to prevent concurrent plan changes.
     */
    public function execute(Subscription $subscription, Plan $targetPlan): SubscriptionPlanChange
    {
        return DB::transaction(function () use ($subscription, $targetPlan): SubscriptionPlanChange {
            // Lock the subscription to prevent concurrent plan changes.
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->firstOrFail();

            if (! $subscription->isActive()) {
                throw ValidationException::withMessages([
                    'subscription' => ['Only active subscriptions can change plans.'],
                ]);
            }

            if (! $targetPlan->isActive()) {
                throw ValidationException::withMessages([
                    'plan_id' => ['The target plan is not active.'],
                ]);
            }

            if ($subscription->plan_id === $targetPlan->id) {
                throw ValidationException::withMessages([
                    'plan_id' => ['The subscription is already on this plan.'],
                ]);
            }

            $now = Carbon::now();

            $change = new SubscriptionPlanChange;
            $change->subscription_id = $subscription->id;
            $change->from_plan_id = $subscription->plan_id;
            $change->to_plan_id = $targetPlan->id;
            $change->effective_at = $now;
            $change->from_base_price = $subscription->base_price;
            $change->from_included_usage_units = $subscription->included_usage_units;
            $change->from_overage_rate = $subscription->overage_rate;
            $change->from_billing_cycle = $subscription->billing_cycle;
            $change->to_base_price = $targetPlan->base_price;
            $change->to_included_usage_units = $targetPlan->included_usage_units;
            $change->to_overage_rate = $targetPlan->overage_rate;
            $change->to_billing_cycle = $targetPlan->billing_cycle;
            $change->save();

            $subscription->plan_id = $targetPlan->id;
            $subscription->base_price = $targetPlan->base_price;
            $subscription->included_usage_units = $targetPlan->included_usage_units;
            $subscription->overage_rate = $targetPlan->overage_rate;
            $subscription->billing_cycle = $targetPlan->billing_cycle;
            $subscription->save();

            return $change->refresh();
        });
    }
}
