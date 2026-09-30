<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MerchantPolicy;
use App\Policies\PlanPolicy;
use App\Policies\SubscriptionPolicy;
use App\Policies\UsageEventPolicy;
use App\Tenancy\MerchantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(MerchantContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::policy(Merchant::class, MerchantPolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Subscription::class, SubscriptionPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(UsageEvent::class, UsageEventPolicy::class);

        // Merchant-scoped rate limit for usage ingestion.
        // 500 events/minute per merchant — high enough for production bursts,
        // low enough to protect the database from runaway clients.
        RateLimiter::for('usage-ingest', function (Request $request): Limit {
            $user = $request->user();
            $key = $user !== null ? $user->merchant_id : 'anonymous';

            return Limit::perMinute(500)->by('usage-ingest:'.$key);
        });
    }
}
