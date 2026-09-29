<?php

declare(strict_types=1);

namespace App\Actions\Customers;

use App\Models\Customer;

final class UpdateCustomer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->refresh();
    }
}
