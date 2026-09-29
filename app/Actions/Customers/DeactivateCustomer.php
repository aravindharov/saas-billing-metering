<?php

declare(strict_types=1);

namespace App\Actions\Customers;

use App\Enums\CustomerStatus;
use App\Models\Customer;

final class DeactivateCustomer
{
    public function execute(Customer $customer): Customer
    {
        $customer->update(['status' => CustomerStatus::Inactive]);

        return $customer->refresh();
    }
}
