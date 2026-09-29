<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Authorization for customer management.
 *
 * Owner: full CRUD access to customers within their merchant.
 * Member: read-only access to customers within their merchant.
 *
 * Tenant isolation is enforced by checking that the customer belongs to
 * the same merchant as the authenticated user.
 */
final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->merchant_id === $customer->merchant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isOwner() && $user->merchant_id === $customer->merchant_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isOwner() && $user->merchant_id === $customer->merchant_id;
    }
}
