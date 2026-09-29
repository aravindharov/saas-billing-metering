<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected static ?string $password = null;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'merchant_id' => Merchant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'role' => UserRole::Member,
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => UserRole::Owner]);
    }

    public function member(): static
    {
        return $this->state(['role' => UserRole::Member]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(['merchant_id' => $merchant->id]);
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }
}
