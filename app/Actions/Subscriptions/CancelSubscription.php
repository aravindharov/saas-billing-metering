<?php

declare(strict_types=1);

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelSubscription
{
    public function execute(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription): Subscription {
            $subscription = Subscription::where('id', $subscription->id)->lockForUpdate()->firstOrFail();

            if (! $subscription->isActive()) {
                throw ValidationException::withMessages([
                    'subscription' => ['Only active subscriptions can be cancelled.'],
                ]);
            }

            $subscription->status = SubscriptionStatus::Cancelled;
            $subscription->cancelled_at = Carbon::now();
            $subscription->save();

            return $subscription->refresh();
        });
    }
}
