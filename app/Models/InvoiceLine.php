<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceLineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $invoice_id
 * @property InvoiceLineType $type
 * @property string $description
 * @property int $quantity
 * @property int $unit_price
 * @property int $amount
 * @property array<string, mixed>|null $metadata
 */
class InvoiceLine extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'amount',
        'metadata',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'type' => InvoiceLineType::class,
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'amount' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
