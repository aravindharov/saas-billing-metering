<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UsageEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Raw usage event — immutable source-of-truth record.
 *
 * @property string $public_id
 * @property int $merchant_id
 * @property int $customer_id
 * @property int $subscription_id
 * @property string $event_id
 * @property int $quantity
 * @property Carbon $occurred_at
 */
class UsageEvent extends Model
{
    /** @use HasFactory<UsageEventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'customer_id',
        'subscription_id',
        'quantity',
        'occurred_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'merchant_id',
        'customer_id',
        'subscription_id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UsageEvent $event): void {
            if (empty($event->public_id)) {
                $event->public_id = (string) Str::ulid();
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

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
