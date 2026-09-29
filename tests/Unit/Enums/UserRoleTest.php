<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function test_owner_role_has_correct_value(): void
    {
        $this->assertSame('owner', UserRole::Owner->value);
    }

    public function test_member_role_has_correct_value(): void
    {
        $this->assertSame('member', UserRole::Member->value);
    }

    public function test_is_owner_returns_true_for_owner(): void
    {
        $this->assertTrue(UserRole::Owner->isOwner());
    }

    public function test_is_owner_returns_false_for_member(): void
    {
        $this->assertFalse(UserRole::Member->isOwner());
    }
}
