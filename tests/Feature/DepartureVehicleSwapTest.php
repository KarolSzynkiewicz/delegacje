<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Enums\VehicleType;
use App\Livewire\DepartureVehicleSwap;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\DepartureVehicleSwapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class DepartureVehicleSwapTest extends TestCase
{
    use RefreshDatabase;

    protected DepartureVehicleSwapService $swap;

    protected Location $base;

    protected Location $field;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-01'));

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->swap = app(DepartureVehicleSwapService::class);
        $this->base = Location::factory()->create(['is_base' => true, 'name' => 'Baza']);
        $this->field = Location::factory()->create(['is_base' => false, 'name' => 'Budowa']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_swaps_trip_vehicle_and_participant_on_site_assignments(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create(['first_name' => 'Marek', 'last_name' => 'Kowalski']);
        $zbig = Employee::factory()->create(['first_name' => 'Zbigniew', 'last_name' => 'Nowak']);

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek, $zbig]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-31', VehiclePosition::DRIVER);
        $this->assignOnSite($departure, $mazda, $zbig, '2026-07-11', '2026-07-31', VehiclePosition::PASSENGER);

        $this->swap->swap($departure, $merc->id, []);

        $this->assertSame($merc->id, $departure->fresh()->vehicle_id);
        $this->assertSame('2026-07-10', $departure->fresh()->event_date->toDateString());
        $this->assertDatabaseHas('vehicle_assignments', [
            'employee_id' => $marek->id,
            'vehicle_id' => $merc->id,
            'logistics_event_id' => $departure->id,
        ]);
        $this->assertDatabaseHas('vehicle_assignments', [
            'employee_id' => $zbig->id,
            'vehicle_id' => $merc->id,
            'logistics_event_id' => $departure->id,
        ]);
        $this->assertDatabaseMissing('vehicle_assignments', [
            'employee_id' => $marek->id,
            'vehicle_id' => $mazda->id,
        ]);
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $departure->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_does_not_move_krzys_unless_confirmed(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $krzys = Employee::factory()->create(['first_name' => 'Krzysztof', 'last_name' => 'Lis']);

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-31');
        $krzysAssignment = VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-11',
            'end_date' => '2026-07-31',
        ]);

        $preview = $this->swap->preview($departure, $merc->id, []);
        $this->assertCount(1, $preview['external_assignments']);
        $this->assertSame($krzys->id, $preview['external_assignments'][0]['employee_id']);

        $this->swap->swap($departure, $merc->id, []);

        $this->assertSame($mazda->id, $krzysAssignment->fresh()->vehicle_id);
        $this->assertSame($merc->id, VehicleAssignment::query()->where('employee_id', $marek->id)->first()->vehicle_id);
    }

    public function test_moves_krzys_only_when_checkbox_id_is_passed(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $krzys = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-31');
        $krzysAssignment = VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-11',
            'end_date' => '2026-07-31',
        ]);

        $this->swap->swap($departure, $merc->id, [$krzysAssignment->id]);

        $this->assertSame($merc->id, $krzysAssignment->fresh()->vehicle_id);
        $this->assertSame($merc->id, $departure->fresh()->vehicle_id);
        $this->assertFalse(
            $departure->participants()->where('employee_id', $krzys->id)->exists()
        );
    }

    public function test_krzys_in_a_hole_between_participant_ranges_is_not_detected(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $zbig = Employee::factory()->create();
        $krzys = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek, $zbig]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-15');
        $this->assignOnSite($departure, $mazda, $zbig, '2026-07-25', '2026-07-31');
        VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-18',
            'end_date' => '2026-07-20',
        ]);

        $preview = $this->swap->preview($departure, $merc->id, []);
        $this->assertSame([], $preview['external_assignments']);
    }

    public function test_blocks_when_new_car_is_too_small_for_passengers(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 7);
        $merc = $this->vehicle('GDA MERC', 1);
        $a = Employee::factory()->create();
        $b = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$a, $b]);

        $this->expectException(ValidationException::class);
        $this->swap->swap($departure, $merc->id, []);
    }

    public function test_blocks_after_arrival_date(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-06-20', '2026-06-21', [$marek]);

        $this->assertFalse($this->swap->canSwap($departure));
        $this->expectException(ValidationException::class);
        $this->swap->swap($departure, $merc->id, []);
    }

    public function test_blocks_public_transport_departure(): void
    {
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $departure = $this->makeDeparture(null, '2026-07-10', '2026-07-11', [$marek]);

        $this->assertFalse($this->swap->canSwap($departure));
        $this->expectException(ValidationException::class);
        $this->swap->swap($departure, $merc->id, []);
    }

    public function test_splits_wider_krzys_assignment_when_confirmed(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $krzys = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-20');
        $krzysAssignment = VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $preview = $this->swap->preview($departure, $merc->id, []);
        $this->assertTrue($preview['external_assignments'][0]['wider']);

        $this->swap->swap($departure, $merc->id, [$krzysAssignment->id]);

        $rows = VehicleAssignment::query()->where('employee_id', $krzys->id)->orderBy('start_date')->get();
        $this->assertTrue($rows->contains(fn (VehicleAssignment $a) => (int) $a->vehicle_id === $mazda->id));
        $this->assertTrue($rows->contains(fn (VehicleAssignment $a) => (int) $a->vehicle_id === $merc->id));
        $mercRow = $rows->first(fn (VehicleAssignment $a) => (int) $a->vehicle_id === $merc->id);
        $this->assertSame('2026-07-11', $mercRow->start_date->toDateString());
        $this->assertSame('2026-07-20', $mercRow->end_date->toDateString());
    }

    public function test_livewire_does_not_move_krzys_without_checkbox(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $krzys = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-31');
        $krzysAssignment = VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-11',
            'end_date' => '2026-07-31',
        ]);

        Livewire::test(DepartureVehicleSwap::class, ['departureId' => $departure->id])
            ->set('newVehicleId', $merc->id)
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('departures.show', $departure));

        $this->assertSame($mazda->id, $krzysAssignment->fresh()->vehicle_id);
        $this->assertSame($merc->id, $departure->fresh()->vehicle_id);
    }

    public function test_livewire_moves_krzys_when_checkbox_is_set(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $marek = Employee::factory()->create();
        $krzys = Employee::factory()->create();

        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);
        $this->assignOnSite($departure, $mazda, $marek, '2026-07-11', '2026-07-31');
        $krzysAssignment = VehicleAssignment::create([
            'employee_id' => $krzys->id,
            'vehicle_id' => $mazda->id,
            'position' => VehiclePosition::PASSENGER,
            'start_date' => '2026-07-11',
            'end_date' => '2026-07-31',
        ]);

        Livewire::test(DepartureVehicleSwap::class, ['departureId' => $departure->id])
            ->set('newVehicleId', $merc->id)
            ->set('confirmedExternalAssignmentIds', [$krzysAssignment->id])
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('departures.show', $departure));

        $this->assertSame($merc->id, $krzysAssignment->fresh()->vehicle_id);
    }

    public function test_candidate_list_only_includes_vehicles_at_base(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $merc = $this->vehicle('GDA MERC', 5);
        $fieldCar = $this->vehicle('GDA FIELD', 5);
        $marek = Employee::factory()->create();
        $other = Employee::factory()->create();

        $this->makeDeparture($fieldCar, '2026-06-01', '2026-06-02', [$other]);
        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);

        $ids = $this->swap->candidateVehicles($departure)->pluck('id');

        $this->assertTrue($ids->contains($merc->id));
        $this->assertFalse($ids->contains($fieldCar->id));
        $this->assertFalse($ids->contains($mazda->id));
    }

    public function test_cannot_swap_to_vehicle_that_is_outside_base(): void
    {
        $mazda = $this->vehicle('GDA MAZDA', 5);
        $fieldCar = $this->vehicle('GDA FIELD', 5);
        $marek = Employee::factory()->create();
        $other = Employee::factory()->create();

        $this->makeDeparture($fieldCar, '2026-06-01', '2026-06-02', [$other]);
        $departure = $this->makeDeparture($mazda, '2026-07-10', '2026-07-11', [$marek]);

        $this->expectException(ValidationException::class);
        $this->swap->swap($departure, $fieldCar->id, []);
    }

    protected function vehicle(string $plates, int $capacity): Vehicle
    {
        return Vehicle::factory()->create([
            'registration_number' => $plates,
            'brand' => 'Test',
            'model' => $plates,
            'capacity' => $capacity,
            'type' => VehicleType::COMPANY_VEHICLE,
        ]);
    }

    /**
     * @param  list<Employee>  $employees
     */
    protected function makeDeparture(?Vehicle $vehicle, string $start, string $end, array $employees): LogisticsEvent
    {
        $departure = LogisticsEvent::query()->create([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => $start,
            'end_date' => $end,
            'from_location_id' => $this->base->id,
            'to_location_id' => $this->field->id,
            'vehicle_id' => $vehicle?->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $this->user->id,
        ]);

        foreach ($employees as $employee) {
            $departure->participants()->create(['employee_id' => $employee->id]);
        }

        return $departure;
    }

    protected function assignOnSite(
        LogisticsEvent $departure,
        Vehicle $vehicle,
        Employee $employee,
        string $start,
        string $end,
        VehiclePosition $position = VehiclePosition::PASSENGER
    ): VehicleAssignment {
        return VehicleAssignment::create([
            'employee_id' => $employee->id,
            'vehicle_id' => $vehicle->id,
            'position' => $position,
            'start_date' => $start,
            'end_date' => $end,
            'logistics_event_id' => $departure->id,
        ]);
    }
}
