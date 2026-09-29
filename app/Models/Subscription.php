<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Tenancy\MerchantContext;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property int $merchant_id
 * @property int $customer_id
 * @property int $plan_id
 * @property SubscriptionStatus $status
 * @property Carbon $started_at
 * @property Carbon $current_period_start
 * @property Carbon $current_period_end
 * @property BillingCycle $billing_cycle
 * @property int $base_price
 * @property int $included_usage_units
 * @property int $overage_rate
 * @property Carbon|null $cancelled_at
 */
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'customer_id',
        'plan_id',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'billing_cycle',
        'base_price',
        'included_usage_units',
        'overage_rate',
        'cancelled_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'merchant_id',
        'customer_id',
        'plan_id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'billing_cycle' => BillingCycle::class,
            'base_price' => 'integer',
            'included_usage_units' => 'integer',
            'overage_rate' => 'integer',
            'started_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription): void {
            if (empty($subscription->public_id)) {
                $subscription->public_id = (string) Str::ulid();
            }
        });
    }

    /** @return BelongsTo<Merchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** @return HasMany<SubscriptionPlanChange, $this> */
    public function planChanges(): HasMany
    {
        return $this->hasMany(SubscriptionPlanChange::class)->orderBy('effective_at');
    }

    /** @return HasMany<UsageEvent, $this> */
    public function usageEvents(): HasMany
    {
        return $this->hasMany(UsageEvent::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $context = app(MerchantContext::class);

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->when($context->resolved(), fn ($q) => $q->where('merchant_id', $context->id()))
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled;
    }
}
