<?php

declare(strict_types=1);

namespace App\Actions\Usage;

use App\Jobs\AggregateDailyUsage;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordUsageEvent
{
    /**
     * Record a raw usage event.
     *
     * Returns [UsageEvent, bool] — the event and whether it was freshly created.
     * The database UNIQUE(merchant_id, event_id) constraint is the final
     * authority against duplicates, even under concurrent requests.
     *
     * @return array{UsageEvent, bool}
     */
    public function execute(
        Merchant $merchant,
        Customer $customer,
        Subscription $subscription,
        string $eventId,
        int $quantity,
        Carbon $occurredAt,
    ): array {
        $this->validate($customer, $subscription, $occurredAt);

        // Fast-path: check for existing event (idempotent retry).
        $existing = UsageEvent::where('merchant_id', $merchant->id)
            ->where('event_id', $eventId)
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        // Attempt insert — the UNIQUE constraint catches any race.
        try {
            return DB::transaction(function () use (
                $merchant,
                $customer,
                $subscription,
                $eventId,
                $quantity,
                $occurredAt,
            ): array {
                $event = new UsageEvent;
                $event->merchant_id = $merchant->id;
                $event->customer_id = $customer->id;
                $event->subscription_id = $subscription->id;
                $event->event_id = $eventId;
                $event->quantity = $quantity;
                $event->occurred_at = $occurredAt;
                $event->save();

                AggregateDailyUsage::dispatch(
                    $merchant->id,
                    $customer->id,
                    $occurredAt->copy()->utc()->format('Y-m-d'),
                )->afterCommit();

                return [$event->refresh(), true];
            });
        } catch (UniqueConstraintViolationException) {
            // Concurrent duplicate — return the existing record.
            $existing = UsageEvent::where('merchant_id', $merchant->id)
                ->where('event_id', $eventId)
                ->firstOrFail();

            return [$existing, false];
        }
    }

    private function validate(Customer $customer, Subscription $subscription, Carbon $occurredAt): void
    {
        if (! $customer->isActive()) {
            throw ValidationException::withMessages([
                'customer_id' => ['The customer is not active.'],
            ]);
        }

        if (! $subscription->isActive()) {
            throw ValidationException::withMessages([
                'subscription_id' => ['The subscription is not active.'],
            ]);
        }

        if ($subscription->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'subscription_id' => ['The subscription does not belong to this customer.'],
            ]);
        }

        // Reject events unreasonably far in the future (> 5 minutes clock skew tolerance).
        $maxFuture = Carbon::now()->addMinutes(5);
        if ($occurredAt->isAfter($maxFuture)) {
            throw ValidationException::withMessages([
                'occurred_at' => ['The event timestamp is too far in the future.'],
            ]);
        }
    }
}
