<?php

namespace Tests\Feature;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Livewire\DeparturePlannerV2;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartureRoutePlannerStepRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Location $base;

    protected Location $field;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $admin = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web']
        );
        $this->user->assignRole($admin);
        $this->actingAs($this->user);
        $this->base = Location::factory()->create(['is_base' => true, 'name' => 'Baza']);
        $this->field = Location::factory()->create(['is_base' => false, 'name' => 'Budowa']);
        $this->vehicle = Vehicle::factory()->create();
    }

    public function test_waypoints_without_metrics_are_not_an_established_route(): void
    {
        $departure = $this->makeDeparture([
            'route_waypoints' => ['loc:'.$this->base->id, 'loc:'.$this->field->id],
            'route_distance' => null,
            'route_duration' => null,
        ]);

        $this->assertFalse($departure->hasRouteData());
        $this->assertFalse($departure->hasEstablishedRoute());
    }

    public function test_distance_and_duration_mark_the_route_as_established(): void
    {
        $departure = $this->makeDeparture([
            'route_waypoints' => ['loc:'.$this->base->id, 'loc:'.$this->field->id],
            'route_distance' => 120.5,
            'route_duration' => 7200,
        ]);

        $this->assertTrue($departure->hasRouteData());
        $this->assertTrue($departure->hasEstablishedRoute());
    }

    public function test_planner_has_three_steps_and_saves_from_step_three(): void
    {
        Livewire::test(DeparturePlannerV2::class)
            ->assertDontSee('Krok 4: Planowanie trasy')
            ->assertSee('Krok 3: Przypisania do pojazdów');
    }

    public function test_incomplete_save_warning_does_not_mention_step_four_route(): void
    {
        Livewire::test(DeparturePlannerV2::class)
            ->set('transportMode', 'own')
            ->set('departureDate', '2026-09-16')
            ->set('endDate', '2026-09-20')
            ->set('vehicleId', $this->vehicle->id)
            ->set('assignmentRanges', [
                '1_1_1' => [
                    'employee_id' => 1,
                    'project_id' => 1,
                    'role_id' => 1,
                    'start_date' => '2026-09-16',
                    'end_date' => '2026-09-20',
                ],
            ])
            ->call('requestSaveDeparture')
            ->assertSet('showIncompleteSaveModal', true)
            ->assertDontSee('Krok 4: niekompletna trasa');
    }

    public function test_departure_show_uses_empty_state_until_route_metrics_exist(): void
    {
        $departure = $this->makeDeparture([
            'route_waypoints' => ['loc:'.$this->base->id, 'loc:'.$this->field->id],
            'route_distance' => null,
            'route_duration' => null,
        ]);

        $this->get(route('departures.show', $departure))
            ->assertOk()
            ->assertSee('Ustal trasę')
            ->assertDontSee('Kolejność z planu wyjazdu');
    }

    public function test_departure_show_renders_route_when_metrics_are_saved(): void
    {
        $departure = $this->makeDeparture([
            'route_waypoints' => ['loc:'.$this->base->id, 'loc:'.$this->field->id],
            'route_distance' => 80,
            'route_duration' => 3600,
        ]);

        $this->get(route('departures.show', $departure))
            ->assertOk()
            ->assertSee('Kolejność z planu wyjazdu')
            ->assertSee('Edytuj trasę')
            ->assertDontSee('Ustal trasę');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function makeDeparture(array $overrides = []): LogisticsEvent
    {
        return LogisticsEvent::query()->create(array_merge([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => '2026-09-16',
            'end_date' => '2026-09-20',
            'from_location_id' => $this->base->id,
            'to_location_id' => $this->field->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => LogisticsEventStatus::COMPLETED,
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
