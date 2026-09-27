<?php

namespace Tests\Feature;

use App\Enums\VehicleLifecycleEventType;
use App\Enums\VehicleRetirementReason;
use App\Livewire\VehiclesTable;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class VehicleRetirementTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_retire_hides_the_vehicle_from_the_fleet_list_until_asked(): void
    {
        $active = Vehicle::factory()->create(['registration_number' => 'WX 10001']);
        $retired = Vehicle::factory()->create([
            'registration_number' => 'WX 99999',
            'retired_at' => now(),
            'retirement_reason' => VehicleRetirementReason::Scrapped,
        ]);

        Livewire::actingAs($this->user)
            ->test(VehiclesTable::class)
            ->assertSee('WX 10001')
            ->assertDontSee('WX 99999');

        Livewire::actingAs($this->user)
            ->test(VehiclesTable::class)
            ->set('showRetired', true)
            ->assertSee('WX 10001')
            ->assertSee('WX 99999')
            ->assertSee('Wycofany');

        $this->assertTrue(Vehicle::operational()->whereKey($active->id)->exists());
        $this->assertFalse(Vehicle::operational()->whereKey($retired->id)->exists());
    }

    public function test_retire_and_reinstate_keep_the_lifecycle_history(): void
    {
        $vehicle = Vehicle::factory()->create(['registration_number' => 'WX 20002']);

        $this->actingAs($this->user)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Przenieś do archiwum')
            ->assertSee('W flocie');

        $this->actingAs($this->user)
            ->post(route('vehicles.retire', $vehicle), [
                'reason' => VehicleRetirementReason::Other->value,
                'note' => 'Stoi w kącie, nie planujemy go.',
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $vehicle->refresh();
        $this->assertNotNull($vehicle->retired_at);
        $this->assertSame(VehicleRetirementReason::Other, $vehicle->retirement_reason);
        $this->assertFalse(Vehicle::operational()->whereKey($vehicle->id)->exists());
        $this->assertDatabaseHas('vehicle_lifecycle_events', [
            'vehicle_id' => $vehicle->id,
            'type' => VehicleLifecycleEventType::Retired->value,
            'reason' => VehicleRetirementReason::Other->value,
        ]);

        $this->actingAs($this->user)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Przywróć do floty')
            ->assertSee('Inne');

        $this->actingAs($this->user)
            ->post(route('vehicles.reinstate', $vehicle))
            ->assertRedirect(route('vehicles.show', $vehicle));

        $vehicle->refresh();
        $this->assertNull($vehicle->retired_at);
        $this->assertNull($vehicle->retirement_reason);
        $this->assertSame(2, $vehicle->lifecycleEvents()->count());
        $this->assertDatabaseHas('vehicle_lifecycle_events', [
            'vehicle_id' => $vehicle->id,
            'type' => VehicleLifecycleEventType::Reinstated->value,
        ]);
    }

    public function test_retire_releases_people_assigned_today_or_later_and_keeps_finished_history(): void
    {
        $vehicle = Vehicle::factory()->create();
        $finished = VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::today()->subMonth()->toDateString(),
            'end_date' => Carbon::today()->subDays(2)->toDateString(),
        ]);
        $ongoing = VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::today()->subWeek()->toDateString(),
            'end_date' => null,
        ]);
        $future = VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Wycofanie zdejmie przypisania');

        $this->actingAs($this->user)
            ->post(route('vehicles.retire', $vehicle), [
                'reason' => VehicleRetirementReason::Other->value,
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $finished->refresh();
        $ongoing->refresh();
        $this->assertSame(Carbon::today()->subDays(2)->toDateString(), $finished->end_date->toDateString());
        $this->assertSame(Carbon::yesterday()->toDateString(), $ongoing->end_date->toDateString());
        $this->assertDatabaseMissing('vehicle_assignments', ['id' => $future->id]);
        $this->assertFalse($vehicle->assignments()->active()->exists());
    }
}
