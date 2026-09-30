<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

/**
 * Invoice access — read-only for all merchant users (owners and members).
 */
final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->merchant_id === $invoice->merchant_id;
    }
}
