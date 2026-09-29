<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use Database\Factories\SubscriptionPlanChangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property int $subscription_id
 * @property int $from_plan_id
 * @property int $to_plan_id
 * @property Carbon $effective_at
 * @property int $from_base_price
 * @property int $from_included_usage_units
 * @property int $from_overage_rate
 * @property BillingCycle $from_billing_cycle
 * @property int $to_base_price
 * @property int $to_included_usage_units
 * @property int $to_overage_rate
 * @property BillingCycle $to_billing_cycle
 */
class SubscriptionPlanChange extends Model
{
    /** @use HasFactory<SubscriptionPlanChangeFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
        'effective_at',
        'from_base_price',
        'from_included_usage_units',
        'from_overage_rate',
        'from_billing_cycle',
        'to_base_price',
        'to_included_usage_units',
        'to_overage_rate',
        'to_billing_cycle',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'effective_at' => 'datetime',
            'from_base_price' => 'integer',
            'from_included_usage_units' => 'integer',
            'from_overage_rate' => 'integer',
            'from_billing_cycle' => BillingCycle::class,
            'to_base_price' => 'integer',
            'to_included_usage_units' => 'integer',
            'to_overage_rate' => 'integer',
            'to_billing_cycle' => BillingCycle::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SubscriptionPlanChange $change): void {
            if (empty($change->public_id)) {
                $change->public_id = (string) Str::ulid();
            }
        });
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return BelongsTo<Plan, $this> */
    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    /** @return BelongsTo<Plan, $this> */
    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }
}
