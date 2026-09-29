<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $public_id
 * @property int $merchant_id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'merchant_id',
    ];

    /** @var list<string> */
    protected $hidden = [
        'id',
        'merchant_id',
        'password',
        'remember_token',
    ];

    /** @return array<string, class-string|string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->public_id)) {
                $user->public_id = (string) Str::ulid();
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

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isMember(): bool
    {
        return $this->role === UserRole::Member;
    }
}
