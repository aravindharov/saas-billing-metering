<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed development data.
     *
     * Development credentials (NEVER use in production):
     *   Merchant slug: acme
     *   Owner:  owner@acme.test / password
     *   Member: member@acme.test / password
     */
    public function run(): void
    {
        $acme = Merchant::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
        ]);

        User::factory()->owner()->forMerchant($acme)->create([
            'name' => 'Acme Owner',
            'email' => 'owner@acme.test',
        ]);

        User::factory()->member()->forMerchant($acme)->create([
            'name' => 'Acme Member',
            'email' => 'member@acme.test',
        ]);
    }
}
