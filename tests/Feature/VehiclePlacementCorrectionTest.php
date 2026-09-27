<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\LocationTrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class VehiclePlacementCorrectionTest extends TestCase
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

    public function test_show_page_offers_an_immediate_placement_correction(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => false]);

        $this->actingAs($this->user)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Lokalizacja')
            ->assertSee('W bazie')
            ->assertSee('Popraw lokalizację');
    }

    public function test_correction_clears_an_open_trip_and_sets_the_flag_now(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => true]);
        $location = Location::factory()->create();

        LogisticsEvent::create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => Carbon::yesterday()->startOfDay(),
            'end_date' => Carbon::now()->addWeek(),
            'vehicle_id' => $vehicle->id,
            'from_location_id' => $location->id,
            'to_location_id' => $location->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $this->user->id,
        ]);

        $tracking = app(LocationTrackingService::class);
        $before = $tracking->getVehicleLocationStatus($vehicle, Carbon::now());
        $this->assertTrue($before['in_transit']);

        $this->actingAs($this->user)
            ->post(route('vehicles.placement-correction', $vehicle), [
                'placement' => 'base',
                'notes' => 'Auto stoi na placu, wyjazd został w systemie.',
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $vehicle->refresh();
        $this->assertFalse($vehicle->outside_base);

        $after = $tracking->getVehicleLocationStatus($vehicle, Carbon::now());
        $this->assertFalse($after['in_transit']);
        $this->assertFalse($after['outside_base']);
        $this->assertStringContainsString('Korekta położenia', (string) $after['source_label']);

        $this->assertArrayNotHasKey(
            $vehicle->id,
            $tracking->inTransitVehicleIds([$vehicle->id], Carbon::now())
        );

        $this->assertDatabaseHas('logistics_events', [
            'vehicle_id' => $vehicle->id,
            'type' => LogisticsEventType::PLACEMENT_CORRECTION->value,
            'sets_outside_base' => false,
        ]);
    }

    public function test_same_placement_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => false]);

        $this->actingAs($this->user)
            ->from(route('vehicles.show', $vehicle))
            ->post(route('vehicles.placement-correction', $vehicle), [
                'placement' => 'base',
                'notes' => 'Bez zmiany stanu.',
            ])
            ->assertRedirect(route('vehicles.show', $vehicle))
            ->assertSessionHasErrors('placement');
    }

    public function test_looking_at_another_day_does_not_overwrite_todays_correction(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => false]);

        $this->actingAs($this->user)
            ->post(route('vehicles.placement-correction', $vehicle), [
                'placement' => 'field',
                'notes' => 'Auto zostało w terenie bez zjazdu.',
            ])
            ->assertRedirect();

        $vehicle->refresh();
        $this->assertTrue($vehicle->outside_base);

        app(LocationTrackingService::class)->getVehicleLocationStatus($vehicle, Carbon::yesterday());

        $vehicle->refresh();
        $this->assertTrue($vehicle->outside_base);
    }

    public function test_correction_to_base_releases_people_still_assigned_to_the_car(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => true]);
        $ongoing = VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::today()->subDays(3)->toDateString(),
            'end_date' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('vehicles.placement-correction', $vehicle), [
                'placement' => 'base',
                'notes' => 'Auto wróciło na plac.',
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $ongoing->refresh();
        $this->assertSame(Carbon::yesterday()->toDateString(), $ongoing->end_date->toDateString());
        $this->assertFalse($vehicle->assignments()->active()->exists());
    }

    public function test_correction_to_the_field_keeps_the_assignments(): void
    {
        $vehicle = Vehicle::factory()->create(['outside_base' => false]);
        $location = Location::factory()->create();
        LogisticsEvent::create([
            'type' => LogisticsEventType::RETURN,
            'event_date' => Carbon::today()->subDays(3)->startOfDay(),
            'end_date' => Carbon::yesterday()->startOfDay(),
            'vehicle_id' => $vehicle->id,
            'from_location_id' => $location->id,
            'to_location_id' => $location->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $this->user->id,
        ]);
        $ongoing = VehicleAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'start_date' => Carbon::today()->subDay()->toDateString(),
            'end_date' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('vehicles.placement-correction', $vehicle), [
                'placement' => 'field',
                'notes' => 'Auto jednak stoi u klienta.',
            ])
            ->assertRedirect(route('vehicles.show', $vehicle));

        $ongoing->refresh();
        $this->assertNull($ongoing->end_date);
    }
}
