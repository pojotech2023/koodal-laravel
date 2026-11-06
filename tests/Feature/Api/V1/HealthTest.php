<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_ok_and_the_standard_envelope(): void
    {
        $response = $this->getJson('/api/v1/health');

        $this->assertEnvelope($response);
        $response->assertOk();
        $response->assertJsonPath('data.status', 'ok');
        $response->assertJsonPath('data.checks.database', true);
        $response->assertJsonPath('data.checks.cache', true);
    }

    public function test_app_version_flags_a_forced_upgrade_below_the_configured_minimum(): void
    {
        config(['matrimony.app.min_build.android' => 50]);

        $response = $this->getJson('/api/v1/app/version?platform=android&build=10');

        $this->assertEnvelope($response);
        $response->assertJsonPath('data.force_upgrade', true);
    }

    public function test_app_version_does_not_force_an_upgrade_when_current(): void
    {
        config(['matrimony.app.min_build.android' => 50]);

        $response = $this->getJson('/api/v1/app/version?platform=android&build=60');

        $this->assertEnvelope($response);
        $response->assertJsonPath('data.force_upgrade', false);
    }

    public function test_a_failure_response_carries_the_error_envelope_shape(): void
    {
        // No such route: exercises the 404 path through the same JSON
        // exception renderer every other error goes through.
        $response = $this->getJson('/api/v1/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertJsonStructure(['success', 'message']);
        $response->assertJsonPath('success', false);
    }
}
