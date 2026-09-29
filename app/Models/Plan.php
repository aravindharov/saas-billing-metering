<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\PlanStatus;
use App\Tenancy\MerchantContext;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property int $merchant_id
 * @property string $name
 * @property int $base_price
 * @property BillingCycle $billing_cycle
 * @property int $included_usage_units
 * @property int $overage_rate
 * @property PlanStatus $status
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'base_price',
        'billing_cycle',
        'included_usage_units',
        'overage_rate',
        'status',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'merchant_id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'included_usage_units' => 'integer',
            'overage_rate' => 'integer',
            'billing_cycle' => BillingCycle::class,
            'status' => PlanStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Plan $plan): void {
            if (empty($plan->public_id)) {
                $plan->public_id = (string) Str::ulid();
            }
        });
    }

    /** @return BelongsTo<Merchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Scope route model binding to the authenticated merchant.
     *
     * Returns null (→ 404) for plans belonging to other merchants,
     * preventing information leakage about whether another merchant's
     * plan exists.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $context = app(MerchantContext::class);

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->when($context->resolved(), fn ($q) => $q->where('merchant_id', $context->id()))
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === PlanStatus::Active;
    }

    public function isArchived(): bool
    {
        return $this->status === PlanStatus::Archived;
    }
}
