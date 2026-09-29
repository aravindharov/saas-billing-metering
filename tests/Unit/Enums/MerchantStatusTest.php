<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\MerchantStatus;
use PHPUnit\Framework\TestCase;

final class MerchantStatusTest extends TestCase
{
    public function test_active_status_has_correct_value(): void
    {
        $this->assertSame('active', MerchantStatus::Active->value);
    }

    public function test_suspended_status_has_correct_value(): void
    {
        $this->assertSame('suspended', MerchantStatus::Suspended->value);
    }
}
