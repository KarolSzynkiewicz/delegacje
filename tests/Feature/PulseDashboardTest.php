<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PulseDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_pulse(): void
    {
        $this->get(route('pulse'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_open_pulse_dashboard(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $this->actingAs($admin)
            ->get(route('pulse'))
            ->assertOk()
            ->assertSee('Pulse')
            ->assertSee('Users by route');
    }

    public function test_non_admin_cannot_open_pulse_dashboard(): void
    {
        $user = User::factory()->create();

        $this->from('/dashboard')
            ->actingAs($user)
            ->get(route('pulse'))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('error');
    }
}
