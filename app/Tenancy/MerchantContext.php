<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Merchant;

/**
 * Holds the resolved merchant for the current request.
 *
 * Bound as a scoped singleton in the container — one instance per request.
 * The merchant is always derived from the authenticated user, never from
 * client-supplied input.
 */
final class MerchantContext
{
    private ?Merchant $merchant = null;

    public function set(Merchant $merchant): void
    {
        $this->merchant = $merchant;
    }

    public function get(): Merchant
    {
        if ($this->merchant === null) {
            throw new \RuntimeException('Merchant context has not been resolved. Ensure the tenant middleware is applied.');
        }

        return $this->merchant;
    }

    public function id(): int
    {
        return $this->get()->id;
    }

    public function resolved(): bool
    {
        return $this->merchant !== null;
    }
}
