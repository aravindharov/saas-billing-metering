<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\BillingCycle;
use PHPUnit\Framework\TestCase;

final class BillingCycleTest extends TestCase
{
    public function test_monthly_has_correct_value(): void
    {
        $this->assertSame('monthly', BillingCycle::Monthly->value);
    }

    public function test_yearly_has_correct_value(): void
    {
        $this->assertSame('yearly', BillingCycle::Yearly->value);
    }

    public function test_all_cases(): void
    {
        $this->assertCount(2, BillingCycle::cases());
    }
}
