<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UsageEventPolicy
{
    /**
     * Any authenticated user within the merchant can ingest usage.
     *
     * Both owners and members may submit usage events because the
     * ingestion endpoint is expected to be called by machine tokens
     * on behalf of the merchant's backend systems.
     */
    public function ingest(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user within the merchant can view daily usage.
     */
    public function viewDailyUsage(User $user): bool
    {
        return true;
    }
}
