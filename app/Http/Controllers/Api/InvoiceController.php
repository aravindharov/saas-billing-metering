<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Tenancy\MerchantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class InvoiceController extends Controller
{
    public function index(Request $request, MerchantContext $context): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Invoice::class);

        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['draft', 'issued'])],
            'customer_id' => ['sometimes', 'string'],
            'subscription_id' => ['sometimes', 'string'],
            'period_from' => ['sometimes', 'date_format:Y-m-d'],
            'period_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:period_from'],
        ]);

        $query = Invoice::where('merchant_id', $context->id())
            ->with(['customer', 'subscription']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $customer = Customer::where('public_id', $request->input('customer_id'))
                ->where('merchant_id', $context->id())
                ->first();

            $query->when(
                $customer,
                fn ($q) => $q->where('customer_id', $customer->id),
                fn ($q) => $q->whereRaw('1 = 0'),
            );
        }

        if ($request->filled('subscription_id')) {
            $subscription = Subscription::where('public_id', $request->input('subscription_id'))
                ->where('merchant_id', $context->id())
                ->first();

            $query->when(
                $subscription,
                fn ($q) => $q->where('subscription_id', $subscription->id),
                fn ($q) => $q->whereRaw('1 = 0'),
            );
        }

        if ($request->filled('period_from')) {
            $query->whereDate('billing_period_start', '>=', $validated['period_from']);
        }

        if ($request->filled('period_to')) {
            $query->whereDate('billing_period_end', '<=', $validated['period_to']);
        }

        $invoices = $query->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['customer', 'subscription.plan', 'lines']);

        return new InvoiceResource($invoice);
    }
}
