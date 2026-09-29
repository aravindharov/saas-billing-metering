<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DailyUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Derived read model — rebuildable from usage_events.
 *
 * @property int $merchant_id
 * @property int $customer_id
 * @property string $usage_date
 * @property int $total_quantity
 */
class DailyUsage extends Model
{
    /** @use HasFactory<DailyUsageFactory> */
    use HasFactory;

    protected $table = 'daily_usage';

    /** @var list<string> */
    protected $fillable = [
        'merchant_id',
        'customer_id',
        'usage_date',
        'total_quantity',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'merchant_id',
        'customer_id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'usage_date' => 'date:Y-m-d',
            'total_quantity' => 'integer',
        ];
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
}
