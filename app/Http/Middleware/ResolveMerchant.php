<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Tenancy\MerchantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the merchant context from the authenticated user.
 *
 * The tenant is ALWAYS derived from the authenticated identity.
 * Client-supplied merchant_id is never trusted for tenant resolution.
 */
final class ResolveMerchant
{
    public function __construct(
        private readonly MerchantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        $merchant = $user->merchant;

        if (! $merchant->isActive()) {
            abort(403, 'Merchant account is suspended.');
        }

        $this->context->set($merchant);

        return $next($request);
    }
}
