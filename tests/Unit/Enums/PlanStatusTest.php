<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PlanStatus;
use PHPUnit\Framework\TestCase;

final class PlanStatusTest extends TestCase
{
    public function test_active_has_correct_value(): void
    {
        $this->assertSame('active', PlanStatus::Active->value);
    }

    public function test_archived_has_correct_value(): void
    {
        $this->assertSame('archived', PlanStatus::Archived->value);
    }

    public function test_all_cases(): void
    {
        $this->assertCount(2, PlanStatus::cases());
    }
}
