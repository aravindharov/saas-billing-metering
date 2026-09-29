<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Customers\CreateCustomer;
use App\Actions\Customers\DeactivateCustomer;
use App\Actions\Customers\UpdateCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Tenancy\MerchantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class CustomerController extends Controller
{
    public function index(Request $request, MerchantContext $context): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Customer::class);

        $query = Customer::where('merchant_id', $context->id());

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('external_reference', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request, MerchantContext $context, CreateCustomer $action): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $customer = $action->execute($context->get(), $request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Customer $customer): CustomerResource
    {
        Gate::authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomer $action): CustomerResource
    {
        Gate::authorize('update', $customer);

        $customer = $action->execute($customer, $request->validated());

        return new CustomerResource($customer);
    }

    public function destroy(Customer $customer, DeactivateCustomer $action): CustomerResource
    {
        Gate::authorize('delete', $customer);

        $customer = $action->execute($customer);

        return new CustomerResource($customer);
    }
}
