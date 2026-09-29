<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

/**
 * Authorization for subscription management.
 *
 * Owner: full access (create, view, change-plan, cancel).
 * Member: read-only access.
 */
final class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->merchant_id === $subscription->merchant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function changePlan(User $user, Subscription $subscription): bool
    {
        return $user->isOwner() && $user->merchant_id === $subscription->merchant_id;
    }

    public function cancel(User $user, Subscription $subscription): bool
    {
        return $user->isOwner() && $user->merchant_id === $subscription->merchant_id;
    }
}
