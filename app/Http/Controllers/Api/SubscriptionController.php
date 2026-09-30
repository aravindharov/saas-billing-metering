<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Billing\GenerateInvoice;
use App\Actions\Subscriptions\CancelSubscription;
use App\Actions\Subscriptions\ChangeSubscriptionPlan;
use App\Actions\Subscriptions\CreateSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePlanRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\SubscriptionPlanChangeResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Tenancy\MerchantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SubscriptionController extends Controller
{
    public function index(Request $request, MerchantContext $context): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Subscription::class);

        $query = Subscription::where('merchant_id', $context->id())
            ->with(['customer', 'plan']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $customer = Customer::where('public_id', $request->input('customer_id'))
                ->where('merchant_id', $context->id())
                ->first();
            if ($customer) {
                $query->where('customer_id', $customer->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('plan_id')) {
            $plan = Plan::where('public_id', $request->input('plan_id'))
                ->where('merchant_id', $context->id())
                ->first();
            if ($plan) {
                $query->where('plan_id', $plan->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $subscriptions = $query->orderBy('created_at', 'desc')->paginate(15);

        return SubscriptionResource::collection($subscriptions);
    }

    public function store(StoreSubscriptionRequest $request, MerchantContext $context, CreateSubscription $action): JsonResponse
    {
        Gate::authorize('create', Subscription::class);

        $customer = Customer::where('public_id', $request->string('customer_id')->toString())
            ->where('merchant_id', $context->id())
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer is invalid.'],
            ]);
        }

        $plan = Plan::where('public_id', $request->string('plan_id')->toString())
            ->where('merchant_id', $context->id())
            ->first();

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan_id' => ['The selected plan is invalid.'],
            ]);
        }

        $subscription = $action->execute($context->get(), $customer, $plan);
        $subscription->load(['customer', 'plan', 'planChanges']);

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Subscription $subscription): SubscriptionResource
    {
        Gate::authorize('view', $subscription);

        $subscription->load(['customer', 'plan', 'planChanges.fromPlan', 'planChanges.toPlan']);

        return new SubscriptionResource($subscription);
    }

    public function changePlan(
        ChangePlanRequest $request,
        Subscription $subscription,
        MerchantContext $context,
        ChangeSubscriptionPlan $action,
    ): JsonResponse {
        Gate::authorize('changePlan', $subscription);

        $targetPlan = Plan::where('public_id', $request->string('plan_id')->toString())
            ->where('merchant_id', $context->id())
            ->first();

        if (! $targetPlan) {
            throw ValidationException::withMessages([
                'plan_id' => ['The selected plan is invalid.'],
            ]);
        }

        $change = $action->execute($subscription, $targetPlan);
        $change->load(['fromPlan', 'toPlan']);

        return (new SubscriptionPlanChangeResource($change))
            ->response()
            ->setStatusCode(200);
    }

    public function cancel(Subscription $subscription, CancelSubscription $action): SubscriptionResource
    {
        Gate::authorize('cancel', $subscription);

        $subscription = $action->execute($subscription);
        $subscription->load(['customer', 'plan', 'planChanges']);

        return new SubscriptionResource($subscription);
    }

    public function generateInvoice(Subscription $subscription, GenerateInvoice $action): JsonResponse
    {
        Gate::authorize('generateInvoice', $subscription);

        $invoice = $action->execute($subscription);
        $invoice->load(['customer', 'subscription.plan', 'lines']);

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(200);
    }
}
