<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * A no-op job that proves the queue infrastructure works.
 * Remove this once real domain jobs exist.
 */
final class ExampleJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $message = 'Queue infrastructure verified.',
    ) {}

    public function handle(): void
    {
        Log::info($this->message);
    }
}
