<?php

declare(strict_types=1);

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\Merchant;

final class CreateCustomer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Merchant $merchant, array $data): Customer
    {
        $customer = $merchant->customers()->create($data);

        return $customer->refresh();
    }
}
