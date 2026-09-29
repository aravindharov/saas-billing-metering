<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Tenancy\MerchantContext;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property int $merchant_id
 * @property string $name
 * @property string $email
 * @property string|null $external_reference
 * @property CustomerStatus $status
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'external_reference',
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
            'status' => CustomerStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            if (empty($customer->public_id)) {
                $customer->public_id = (string) Str::ulid();
            }
        });
    }

    /** @return BelongsTo<Merchant, $this> */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * Scope route model binding to the authenticated merchant.
     *
     * Returns null (→ 404) for customers belonging to other merchants,
     * preventing information leakage about whether another merchant's
     * customer exists.
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
        return $this->status === CustomerStatus::Active;
    }

    public function isInactive(): bool
    {
        return $this->status === CustomerStatus::Inactive;
    }
}
