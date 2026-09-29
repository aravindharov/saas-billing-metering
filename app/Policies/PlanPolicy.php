<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

/**
 * Authorization for plan management.
 *
 * Owner: full CRUD access to plans within their merchant.
 * Member: read-only access to plans within their merchant.
 *
 * Tenant isolation is enforced by checking that the plan belongs to
 * the same merchant as the authenticated user. This prevents any
 * cross-tenant access regardless of role.
 */
final class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->merchant_id === $plan->merchant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->isOwner() && $user->merchant_id === $plan->merchant_id;
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->isOwner() && $user->merchant_id === $plan->merchant_id;
    }
}
