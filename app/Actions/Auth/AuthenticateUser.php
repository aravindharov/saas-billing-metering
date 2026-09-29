<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class AuthenticateUser
{
    /**
     * Authenticate a user by merchant slug, email, and password.
     *
     * Returns the user on success or null on failure. The null result is
     * intentionally opaque — callers should not distinguish between
     * "merchant not found", "user not found", and "wrong password".
     *
     * @return array{user: User, token: string}|null
     */
    public function execute(string $merchantSlug, string $email, string $password): ?array
    {
        $merchant = Merchant::where('slug', $merchantSlug)->first();

        if (! $merchant || ! $merchant->isActive()) {
            Log::info('Login failed: merchant not found or inactive.', ['merchant' => $merchantSlug]);

            return null;
        }

        $user = $merchant->users()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            Log::info('Login failed: invalid credentials.', [
                'merchant' => $merchantSlug,
                'email' => $email,
            ]);

            return null;
        }

        $token = $user->createToken('api')->plainTextToken;

        Log::info('Login successful.', [
            'merchant' => $merchantSlug,
            'user_id' => $user->public_id,
        ]);

        return ['user' => $user, 'token' => $token];
    }
}
