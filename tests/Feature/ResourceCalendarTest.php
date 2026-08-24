<?php

namespace Tests\Feature;

use App\Livewire\ResourceCalendar;
use App\Models\Employee;
use App\Models\Rotation;
use App\Models\Sprint;
use App\Models\User;
use App\Support\Calendar\CalendarRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResourceCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $adminRole = Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->admin->assignRole($adminRole);
        }
    }

    public function test_calendar_page_renders_the_livewire_component(): void
    {
        $this->actingAs($this->admin)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Kalendarz')
            ->assertSeeLivewire(ResourceCalendar::class);
    }

    public function test_month_view_shows_events_from_every_registered_layer(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski']);
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => CarbonImmutable::today()->startOfMonth(),
            'end_date' => CarbonImmutable::today()->endOfMonth(),
        ]);
        Sprint::factory()->create(['name' => 'Sprint kalendarzowy']);

        $component = Livewire::actingAs($this->admin)->test(ResourceCalendar::class);

        $component->assertSet('view', 'month')
            ->assertSee('Jan Kowalski')
            ->assertSee('Sprint kalendarzowy');

        // Ta sama rotacja musi być widoczna w każdym z trzech zakresów.
        $component->call('setView', 'week')->assertSee('Jan Kowalski');
        $component->call('goToDay', CarbonImmutable::today()->toDateString())
            ->assertSet('view', 'day')
            ->assertSee('Jan Kowalski');
    }

    public function test_layer_can_be_toggled_off_and_is_reported_as_an_active_filter(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Anna', 'last_name' => 'Nowak']);
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => CarbonImmutable::today()->startOfMonth(),
            'end_date' => CarbonImmutable::today()->endOfMonth(),
        ]);

        $component = Livewire::actingAs($this->admin)->test(ResourceCalendar::class);

        $component->assertSee('Anna Nowak')
            ->assertDontSee('Filtry:');

        $component->call('toggleLayer', 'rotations')
            ->assertSet('hidden', 'rotations')
            ->assertSee('Filtry:')
            ->assertSee('Ukryto: Rotacje pracowników')
            ->assertDontSee('Anna Nowak');

        // „Wyczyść” wraca do stanu maksymalnie pokazującego, nie do jakiegoś domyślnego zawężenia.
        $component->call('clearFilters')
            ->assertSet('hidden', '')
            ->assertSet('search', '')
            ->assertSee('Anna Nowak')
            ->assertDontSee('Filtry:');
    }

    public function test_navigation_switches_period_and_view(): void
    {
        $today = CarbonImmutable::today();

        Livewire::actingAs($this->admin)
            ->test(ResourceCalendar::class)
            ->call('next')
            ->assertSet('anchor', $today->addMonthNoOverflow()->toDateString())
            ->call('goToToday')
            ->assertSet('anchor', $today->toDateString())
            ->call('setView', 'week')
            ->assertSet('view', 'week')
            ->call('previous')
            ->assertSet('anchor', $today->subWeek()->toDateString())
            ->call('goToDay', $today->toDateString())
            ->assertSet('view', 'day')
            ->assertSet('anchor', $today->toDateString());
    }

    public function test_search_narrows_events_across_layers(): void
    {
        $employee = Employee::factory()->create(['first_name' => 'Piotr', 'last_name' => 'Zieliński']);
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => CarbonImmutable::today()->startOfMonth(),
            'end_date' => CarbonImmutable::today()->endOfMonth(),
        ]);
        Sprint::factory()->create(['name' => 'Sprint niepowiązany']);

        Livewire::actingAs($this->admin)
            ->test(ResourceCalendar::class)
            ->set('search', 'Zieliński')
            ->assertSee('Piotr Zieliński')
            ->assertDontSee('Sprint niepowiązany');
    }

    /**
     * Regresja: `groupBy()` przenumerowuje pozycje, więc przełączniki wysyłały indeks w grupie
     * („0”, „1”) zamiast klucza warstwy, a powtórzone `wire:key` rozjeżdżały morphing Livewire —
     * kliknięcie jednej warstwy przestawiało inne, a pierwszej w grupie nie dało się odznaczyć.
     */
    public function test_layer_toggles_use_layer_keys_not_positions_within_group(): void
    {
        $component = Livewire::actingAs($this->admin)->test(ResourceCalendar::class);
        $html = $component->html();

        $keys = app(CalendarRegistry::class)->visibleFor($this->admin)->keys();
        $this->assertNotEmpty($keys);

        foreach ($keys as $key) {
            $this->assertStringContainsString("toggleLayer('{$key}')", $html);
            $this->assertStringContainsString('wire:key="rc-layer-'.$key.'"', $html);
        }

        $this->assertStringNotContainsString("toggleLayer('0')", $html);

        // Pierwsza warstwa w grupie musi dać się wyłączyć pojedynczo.
        $component->call('toggleLayer', 'tasks')->assertSet('hidden', 'tasks');
    }

    /**
     * Zdarzenie trwające kilka dni ma być JEDNYM paskiem rozciągniętym na kolumny siatki
     * (jak w Google Calendar), a nie osobnym kafelkiem w każdym dniu.
     */
    public function test_multi_day_event_renders_as_one_spanning_bar(): void
    {
        $monday = CarbonImmutable::today()->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY)->addWeek();

        $employee = Employee::factory()->create(['first_name' => 'Karol', 'last_name' => 'Długi']);
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => $monday,
            'end_date' => $monday->addDays(2),
        ]);

        $html = Livewire::actingAs($this->admin)
            ->test(ResourceCalendar::class)
            ->set('anchor', $monday->toDateString())
            ->set('search', 'Długi')
            ->html();

        $this->assertSame(
            1,
            substr_count($html, 'wire:key="rc-bar-'),
            'Trzydniowa rotacja ma być jednym paskiem, a nie kafelkiem w każdym dniu.'
        );
        $this->assertStringContainsString('grid-column: 1 / span 3;', $html);
    }

    /** Zdarzenie przechodzące przez granicę tygodnia dzieli się na dwa paski ze strzałkami kontynuacji. */
    public function test_event_crossing_a_week_boundary_is_split_and_marked_as_continuing(): void
    {
        $sunday = CarbonImmutable::today()->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY)->addWeek()->addDays(6);

        $employee = Employee::factory()->create(['first_name' => 'Ewa', 'last_name' => 'Przełom']);
        Rotation::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => $sunday,
            'end_date' => $sunday->addDays(2),
        ]);

        $html = Livewire::actingAs($this->admin)
            ->test(ResourceCalendar::class)
            ->set('anchor', $sunday->toDateString())
            ->set('search', 'Przełom')
            ->html();

        $this->assertSame(2, substr_count($html, 'wire:key="rc-bar-'));
        $this->assertStringContainsString('is-cont-after', $html);
        $this->assertStringContainsString('is-cont-before', $html);
    }

    public function test_layers_are_hidden_from_users_without_the_matching_permission(): void
    {
        $user = User::factory()->create();

        $keys = app(CalendarRegistry::class)->visibleFor($user)->keys();

        $this->assertNotContains('rotations', $keys->all());
        $this->assertNotContains('tasks', $keys->all());
    }
}
