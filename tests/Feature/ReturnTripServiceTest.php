<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventType;
use App\Models\Accommodation;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Role;
use App\Models\Rotation;
use App\Models\TransportCost;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\ReturnTripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReturnTripServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReturnTripService $service;

    protected Location $baseLocation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $this->service = app(ReturnTripService::class);

        // Create base location
        $this->baseLocation = Location::factory()->create(['is_base' => true]);
    }

    /** @test */
    public function it_creates_return_trip_for_employees_with_active_assignments()
    {
        $employee = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $vehicle = Vehicle::factory()->create(['type' => 'company_vehicle']);
        $location = Location::factory()->create();
        $accommodation = Accommodation::factory()->create(['location_id' => $location->id]);

        // Create rotation
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        $returnDate = now();

        // Create active project assignment
        $projectAssignment = ProjectAssignment::create([
            'employee_id' => $employee->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(5),
            'end_date' => $returnDate->copy()->addDays(5),
        ]);

        // Create active accommodation assignment
        $accommodationAssignment = AccommodationAssignment::create([
            'employee_id' => $employee->id,
            'accommodation_id' => $accommodation->id,
            'start_date' => $returnDate->copy()->subDays(3),
            'end_date' => $returnDate->copy()->addDays(3),
        ]);

        $event = $this->service->createReturn(
            [$employee->id],
            $returnDate,
            $vehicle,
            'Test return trip'
        );

        $this->assertInstanceOf(LogisticsEvent::class, $event);
        $this->assertEquals(LogisticsEventType::RETURN, $event->type);
        $this->assertEquals($this->baseLocation->id, $event->to_location_id);

        // Check assignments end dates are updated
        $projectAssignment->refresh();
        $this->assertEquals($returnDate->format('Y-m-d'), $projectAssignment->end_date->format('Y-m-d'));

        // Zjazd nie tworzy już VehicleAssignment legu powrotnego (tylko zdarzenie + uczestnicy)
        $this->assertFalse(
            VehicleAssignment::where('employee_id', $employee->id)
                ->where('vehicle_id', $vehicle->id)
                ->where('is_return_trip', true)
                ->exists()
        );

        $this->assertTrue(
            $event->participants()->where('employee_id', $employee->id)->exists()
        );
    }

    /** @test */
    public function it_shortens_active_rotation_to_return_date()
    {
        $employee = Employee::factory()->create();

        $returnDate = now()->startOfDay();
        $originalRotationEnd = $returnDate->copy()->addDays(10);

        $rotation = Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => $returnDate->copy()->subDays(10),
            'end_date' => $originalRotationEnd,
        ]);

        $event = $this->service->createReturn(
            [$employee->id],
            $returnDate,
            null,
            'Zjazd – test skrócenia rotacji'
        );

        // Rotacja powinna być skrócona do dnia zjazdu
        $rotation->refresh();
        $this->assertEquals(
            $returnDate->format('Y-m-d'),
            $rotation->end_date->format('Y-m-d')
        );

        // Powinien istnieć uczestnik powiązany z rotacją (typ 'rotation') z zapisaną oryginalną datą końca
        $participant = $event->participants()
            ->where('employee_id', $employee->id)
            ->where('assignment_type', 'rotation')
            ->where('assignment_id', $rotation->id)
            ->first();

        $this->assertNotNull($participant, 'Brak uczestnika z assignment_type=rotation.');
        $this->assertEquals(
            $originalRotationEnd->format('Y-m-d'),
            $participant->original_end_date->format('Y-m-d')
        );
    }

    /** @test */
    public function it_restores_rotation_end_date_when_zjazd_is_reversed()
    {
        $employee = Employee::factory()->create();

        $returnDate = now()->startOfDay();
        $originalRotationEnd = $returnDate->copy()->addDays(12);

        $rotation = Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => $returnDate->copy()->subDays(5),
            'end_date' => $originalRotationEnd,
        ]);

        $event = $this->service->createReturn(
            [$employee->id],
            $returnDate,
            null,
            'Zjazd – test cofnięcia'
        );

        $rotation->refresh();
        $this->assertEquals($returnDate->format('Y-m-d'), $rotation->end_date->format('Y-m-d'));

        $this->service->reverseZjazd($event);

        $rotation->refresh();
        $this->assertEquals(
            $originalRotationEnd->format('Y-m-d'),
            $rotation->end_date->format('Y-m-d')
        );
    }

    /** @test */
    public function it_unregisters_one_employee_and_restores_only_their_shortened_assignments(): void
    {
        $leaving = Employee::factory()->create();
        $staying = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        $returnDate = now()->startOfDay();
        $originalEnd = $returnDate->copy()->addDays(8);

        $leavingAssignment = ProjectAssignment::create([
            'employee_id' => $leaving->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(4),
            'end_date' => $originalEnd,
        ]);
        $stayingAssignment = ProjectAssignment::create([
            'employee_id' => $staying->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(4),
            'end_date' => $originalEnd,
        ]);

        $event = $this->service->createReturn(
            [$leaving->id, $staying->id],
            $returnDate,
            null,
            'Zjazd – wypis jednej osoby'
        );

        $ticket = TransportCost::create([
            'logistics_event_id' => $event->id,
            'cost_type' => 'ticket',
            'amount' => 120,
            'currency' => 'PLN',
            'cost_date' => $returnDate->toDateString(),
            'notes' => 'Bilet powrotny – '.$leaving->full_name,
            'created_by' => auth()->id(),
        ]);

        $result = $this->service->removeParticipant($event, $leaving->id);

        $leavingAssignment->refresh();
        $stayingAssignment->refresh();

        $this->assertSame($originalEnd->format('Y-m-d'), $leavingAssignment->end_date->format('Y-m-d'));
        $this->assertSame($returnDate->format('Y-m-d'), $stayingAssignment->end_date->format('Y-m-d'));
        $this->assertFalse($event->participants()->where('employee_id', $leaving->id)->exists());
        $this->assertTrue($event->participants()->where('employee_id', $staying->id)->exists());
        $this->assertDatabaseHas('transport_costs', [
            'id' => $ticket->id,
            'logistics_event_id' => $event->id,
            'cost_type' => 'ticket',
        ]);
        $this->assertGreaterThan(0, $result['assignments_restored']);
        $this->assertSame(LogisticsEventType::RETURN, $event->fresh()->type);
        $this->assertNotSame(\App\Enums\LogisticsEventStatus::CANCELLED, $event->fresh()->status);
    }

    /** @test */
    public function it_restores_open_ended_assignment_when_unregistering_one_person(): void
    {
        $leaving = Employee::factory()->create();
        $staying = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $returnDate = now()->startOfDay();

        $openAssignment = ProjectAssignment::create([
            'employee_id' => $leaving->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(2),
            'end_date' => null,
        ]);
        ProjectAssignment::create([
            'employee_id' => $staying->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(2),
            'end_date' => $returnDate->copy()->addDays(6),
        ]);

        $event = $this->service->createReturn([$leaving->id, $staying->id], $returnDate, null, 'bezterminowe');

        $openAssignment->refresh();
        $this->assertSame($returnDate->format('Y-m-d'), $openAssignment->end_date->format('Y-m-d'));

        $this->service->removeParticipant($event, $leaving->id);

        $openAssignment->refresh();
        $this->assertNull($openAssignment->end_date);
    }

    /** @test */
    public function it_refuses_to_unregister_the_last_return_trip_participant(): void
    {
        $employee = Employee::factory()->create();
        $returnDate = now()->startOfDay();

        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => $returnDate->copy()->subDays(3),
            'end_date' => $returnDate->copy()->addDays(9),
        ]);

        $event = $this->service->createReturn([$employee->id], $returnDate, null, 'sam');

        try {
            $this->service->removeParticipant($event, $employee->id);
            $this->fail('Wypisanie ostatniego uczestnika powinno zostać odrzucone.');
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first();
            $this->assertStringContainsString('ostatniego uczestnika', (string) $message);
        }

        $this->assertTrue($event->participants()->where('employee_id', $employee->id)->exists());
    }

    /** @test */
    public function show_page_lets_an_admin_unregister_one_person_and_keep_the_ticket(): void
    {
        $admin = \App\Models\User::factory()->create();
        \Spatie\Permission\Models\Role::findOrCreate('administrator', 'web');
        $admin->assignRole('administrator');
        $this->actingAs($admin);

        $leaving = Employee::factory()->create();
        $staying = Employee::factory()->create();
        $project = Project::factory()->create();
        $role = Role::factory()->create();
        $returnDate = now()->startOfDay();
        $originalEnd = $returnDate->copy()->addDays(5);

        $leavingAssignment = ProjectAssignment::create([
            'employee_id' => $leaving->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(2),
            'end_date' => $originalEnd,
        ]);
        ProjectAssignment::create([
            'employee_id' => $staying->id,
            'project_id' => $project->id,
            'role_id' => $role->id,
            'start_date' => $returnDate->copy()->subDays(2),
            'end_date' => $originalEnd,
        ]);

        $event = $this->service->createReturn([$leaving->id, $staying->id], $returnDate, null, 'http');

        TransportCost::create([
            'logistics_event_id' => $event->id,
            'cost_type' => 'ticket',
            'amount' => 80,
            'currency' => 'PLN',
            'cost_date' => $returnDate->toDateString(),
            'notes' => 'Bilet powrotny – '.$leaving->full_name,
            'created_by' => $admin->id,
        ]);

        $this->get(route('return-trips.show', $event))
            ->assertOk()
            ->assertSee('Wypisz')
            ->assertSee('Koszt biletu zostanie', false);

        $this->post(route('return-trips.participants.remove', [$event, $leaving]))
            ->assertRedirect(route('return-trips.show', $event))
            ->assertSessionHas('success');

        $leavingAssignment->refresh();
        $this->assertSame($originalEnd->format('Y-m-d'), $leavingAssignment->end_date->format('Y-m-d'));
        $this->assertFalse($event->participants()->where('employee_id', $leaving->id)->exists());
        $this->assertTrue($event->participants()->where('employee_id', $staying->id)->exists());
        $this->assertDatabaseHas('transport_costs', [
            'logistics_event_id' => $event->id,
            'cost_type' => 'ticket',
            'amount' => 80,
        ]);
    }
}
