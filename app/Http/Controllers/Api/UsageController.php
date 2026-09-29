<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Usage\RecordUsageEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsageEventRequest;
use App\Http\Resources\UsageEventResource;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Tenancy\MerchantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class UsageController extends Controller
{
    public function store(
        StoreUsageEventRequest $request,
        MerchantContext $context,
        RecordUsageEvent $action,
    ): JsonResponse {
        Gate::authorize('ingest', UsageEvent::class);

        $customer = Customer::where('public_id', $request->string('customer_id')->toString())
            ->where('merchant_id', $context->id())
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' => ['The selected customer is invalid.'],
            ]);
        }

        $subscription = Subscription::where('public_id', $request->string('subscription_id')->toString())
            ->where('merchant_id', $context->id())
            ->first();

        if (! $subscription) {
            throw ValidationException::withMessages([
                'subscription_id' => ['The selected subscription is invalid.'],
            ]);
        }

        $occurredAt = Carbon::parse($request->input('occurred_at'))->utc();

        [$event, $created] = $action->execute(
            $context->get(),
            $customer,
            $subscription,
            $request->string('event_id')->toString(),
            (int) $request->integer('quantity'),
            $occurredAt,
        );

        $event->load(['customer', 'subscription']);

        $resource = new UsageEventResource($event);

        return $resource
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}
