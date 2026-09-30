<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

final class MerchantPolicy
{
    public function viewDashboard(User $user, Merchant $merchant): bool
    {
        return $user->merchant_id === $merchant->id;
    }
}
