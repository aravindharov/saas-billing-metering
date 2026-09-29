<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Member = 'member';

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }
}
