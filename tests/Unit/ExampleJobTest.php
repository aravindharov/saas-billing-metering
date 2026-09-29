<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\ExampleJob;
use PHPUnit\Framework\TestCase;

final class ExampleJobTest extends TestCase
{
    public function test_example_job_stores_message(): void
    {
        $job = new ExampleJob('test message');

        $this->assertSame('test message', $job->message);
    }

    public function test_example_job_has_default_message(): void
    {
        $job = new ExampleJob;

        $this->assertSame('Queue infrastructure verified.', $job->message);
    }
}
