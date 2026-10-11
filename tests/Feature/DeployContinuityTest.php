<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeployContinuityTest extends TestCase
{
    public function test_health_stays_a_plain_ok_for_railway(): void
    {
        $this->get('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonMissingPath('release');
    }

    public function test_login_page_does_not_poll_for_deploys(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('name="app-release"', false)
            ->assertDontSee('app-health', false);
    }
}
