<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailyUsageResource;
use App\Models\Customer;
use App\Models\DailyUsage;
use App\Models\UsageEvent;
use App\Tenancy\MerchantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DailyUsageController extends Controller
{
    public function index(Request $request, MerchantContext $context): AnonymousResourceCollection
    {
        Gate::authorize('viewDailyUsage', UsageEvent::class);

        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'from' => ['sometimes', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'required_with:from', 'after_or_equal:from'],
            'customer_id' => ['sometimes', 'string'],
        ]);

        if (isset($validated['from'], $validated['to'])) {
            $from = Carbon::parse($validated['from']);
            $to = Carbon::parse($validated['to']);
            if ($from->diffInDays($to) > 366) {
                throw ValidationException::withMessages([
                    'to' => ['The date range may not exceed 366 days.'],
                ]);
            }
        }

        $query = DailyUsage::where('merchant_id', $context->id())
            ->with('customer');

        if ($request->filled('date')) {
            $query->where('usage_date', $request->input('date'));
        }

        if ($request->filled('from')) {
            $query->where('usage_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('usage_date', '<=', $request->input('to'));
        }

        // Default: if no date filters provided, limit to last 31 days.
        if (! $request->filled('date') && ! $request->filled('from') && ! $request->filled('to')) {
            $query->where('usage_date', '>=', now()->subDays(31)->toDateString());
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

        $results = $query->orderBy('usage_date', 'desc')
            ->orderBy('customer_id')
            ->paginate(50);

        return DailyUsageResource::collection($results);
    }
}
