<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Subscriptions\CreateSubscription;
use App\Enums\BillingCycle;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
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

        Plan::factory()->forMerchant($acme)->create([
            'name' => 'Starter',
            'base_price' => 9900,
            'billing_cycle' => BillingCycle::Monthly,
            'included_usage_units' => 1000,
            'overage_rate' => 5,
        ]);

        Plan::factory()->forMerchant($acme)->create([
            'name' => 'Professional',
            'base_price' => 49900,
            'billing_cycle' => BillingCycle::Monthly,
            'included_usage_units' => 10000,
            'overage_rate' => 3,
        ]);

        Plan::factory()->forMerchant($acme)->yearly()->create([
            'name' => 'Enterprise',
            'base_price' => 499900,
            'billing_cycle' => BillingCycle::Yearly,
            'included_usage_units' => 100000,
            'overage_rate' => 2,
        ]);

        $john = Customer::factory()->forMerchant($acme)->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'external_reference' => 'CRM-10001',
        ]);

        Customer::factory()->forMerchant($acme)->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'external_reference' => 'CRM-10002',
        ]);

        Customer::factory()->forMerchant($acme)->inactive()->create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
        ]);

        $starter = Plan::where('merchant_id', $acme->id)->where('name', 'Starter')->firstOrFail();
        app(CreateSubscription::class)->execute($acme, $john, $starter);
    }
}
