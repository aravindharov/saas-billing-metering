<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_returns_ok_when_services_are_available(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'checks' => ['database', 'redis'],
        ]);
    }

    public function test_health_endpoint_is_accessible_without_authentication(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
    }
}
