<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleVinTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->admin->assignRole($adminRole);
        }
    }

    public function test_show_page_displays_vin_above_notes(): void
    {
        $vehicle = Vehicle::factory()->create([
            'vin' => 'WVWZZZ1JZYW386752',
            'notes' => 'Notatka serwisowa',
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('VIN', false)
            ->assertSee('WVWZZZ1JZYW386752', false)
            ->assertSee('Notatki', false)
            ->assertSee('Notatka serwisowa', false)
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Notatki'),
            strpos($html, 'VIN'),
            'Kafelek VIN powinien być nad notatkami.'
        );
    }

    public function test_update_saves_normalized_vin(): void
    {
        $vehicle = Vehicle::factory()->create(['vin' => null]);

        $this->actingAs($this->admin)
            ->put(route('vehicles.update', $vehicle), [
                'registration_number' => $vehicle->registration_number,
                'technical_condition' => 'good',
                'vin' => ' wvwzzz1jzyw386752 ',
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $this->assertSame('WVWZZZ1JZYW386752', $vehicle->fresh()->vin);
    }

    public function test_update_rejects_invalid_vin(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->actingAs($this->admin)
            ->from(route('vehicles.edit', $vehicle))
            ->put(route('vehicles.update', $vehicle), [
                'registration_number' => $vehicle->registration_number,
                'technical_condition' => 'good',
                'vin' => 'TOO-SHORT',
            ])
            ->assertRedirect(route('vehicles.edit', $vehicle))
            ->assertSessionHasErrors('vin');
    }
}
