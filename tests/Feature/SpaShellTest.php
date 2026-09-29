<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class SpaShellTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_root_url_returns_the_spa_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('app');
    }

    public function test_unknown_paths_fall_through_to_the_spa(): void
    {
        $response = $this->get('/some/frontend/route');

        $response->assertOk();
        $response->assertViewIs('app');
    }
}
