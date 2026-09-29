<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CustomerStatus;
use PHPUnit\Framework\TestCase;

final class CustomerStatusTest extends TestCase
{
    public function test_active_has_correct_value(): void
    {
        $this->assertSame('active', CustomerStatus::Active->value);
    }

    public function test_inactive_has_correct_value(): void
    {
        $this->assertSame('inactive', CustomerStatus::Inactive->value);
    }

    public function test_all_cases(): void
    {
        $this->assertCount(2, CustomerStatus::cases());
    }
}
