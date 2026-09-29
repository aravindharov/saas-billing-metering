<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MerchantStatus;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property string $name
 * @property string $slug
 * @property MerchantStatus $status
 */
class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'status' => MerchantStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant): void {
            if (empty($merchant->public_id)) {
                $merchant->public_id = (string) Str::ulid();
            }
        });
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isActive(): bool
    {
        return $this->status === MerchantStatus::Active;
    }
}
