<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
final class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'public_id' => (string) Str::ulid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'status' => MerchantStatus::Active,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => MerchantStatus::Suspended]);
    }
}
