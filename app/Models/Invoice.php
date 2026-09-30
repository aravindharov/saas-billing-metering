<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Tenancy\MerchantContext;
use Database\Factories\InvoiceFactory;
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
 * @property int $subscription_id
 * @property Carbon $billing_period_start
 * @property Carbon $billing_period_end
 * @property int $subtotal
 * @property int $total
 * @property InvoiceStatus $status
 * @property Carbon|null $issued_at
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'customer_id',
        'subscription_id',
        'billing_period_start',
        'billing_period_end',
        'subtotal',
        'total',
        'status',
        'issued_at',
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
            'billing_period_start' => 'datetime',
            'billing_period_end' => 'datetime',
            'subtotal' => 'integer',
            'total' => 'integer',
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->public_id)) {
                $invoice->public_id = (string) Str::ulid();
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

    /** @return HasMany<InvoiceLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
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

    public function isIssued(): bool
    {
        return $this->status === InvoiceStatus::Issued;
    }
}
