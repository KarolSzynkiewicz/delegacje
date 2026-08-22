<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemType;
use App\Livewire\TasksGrid;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regresja dla zgłoszenia: "czasem nie świeci się info o odpalonych żadnych
 * filtrach a wyświetla się 15 zadań, w czasie gdy ustawię filtr na status:
 * wszystkie — wyświetla się ich 129 [...] nie ma opcji ich wyczyścić".
 *
 * Domyślny status ("aktywne") i domyślny zestaw typów (bez oddzwonień) NADAL
 * zawężają wynik na starcie — ale teraz zawsze widać to jako chip, a
 * "Wyczyść" faktycznie pokazuje wszystko, zamiast wracać do tego samego,
 * cichego domyślnego zawężenia.
 */
class TasksGridFilterTransparencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }

        $this->other = User::factory()->create(['name' => 'Kolega']);
    }

    public function test_default_status_filter_is_always_visible_and_clear_filters_shows_everything(): void
    {
        ProjectTask::query()->create([
            'name' => 'Aktywne zadanie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Dawno zakończone zadanie',
            'status' => TaskStatus::COMPLETED,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)->test(TasksGrid::class);

        // Domyślnie (bez żadnej interakcji) status zawęża do aktywnych — to
        // MUSI być widoczne jako chip, inaczej user nie wie, że coś ukryto.
        $component->assertSee('Filtry:')
            ->assertSee('Status: Aktywne')
            ->assertSee('Aktywne zadanie')
            ->assertDontSee('Dawno zakończone zadanie');

        // "Wyczyść" ma realnie pokazać wszystko — nie wrócić do tego samego
        // domyślnego zawężenia, które user właśnie próbował zdjąć.
        $component->call('clearFilters')
            ->assertSet('status', 'all')
            ->assertDontSee('Filtry:')
            ->assertSee('Aktywne zadanie')
            ->assertSee('Dawno zakończone zadanie');
    }

    public function test_removing_status_chip_switches_to_all_instead_of_bouncing_back_to_default(): void
    {
        ProjectTask::query()->create([
            'name' => 'Zamknięte dawno',
            'status' => TaskStatus::COMPLETED,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TasksGrid::class)
            ->assertDontSee('Zamknięte dawno')
            ->call('clearFilter', 'status')
            ->assertSet('status', 'all')
            ->assertSee('Zamknięte dawno');
    }

    public function test_type_checkbox_filter_replaces_hide_callbacks_toggle(): void
    {
        $component = Livewire::actingAs($this->user)->test(TasksGrid::class);

        // Domyślnie Oddzwonienia są odznaczone (jak dawny hideCallbacks=true),
        // ale to jest widoczne jako chip, a nie cichy, niewzruszalny domyślny stan.
        $component->assertSee('Typ pracy: bez Oddzwonienie');

        $component->call('toggleType', WorkItemType::Callback->value)
            ->assertSet('selectedTypes', array_map(
                fn ($t) => $t->value,
                WorkItemType::cases()
            ))
            ->assertDontSee('Typ pracy:');
    }

    public function test_assigned_filter_dropdown_replaces_my_tasks_only_boolean(): void
    {
        ProjectTask::query()->create([
            'name' => 'Moje zadanie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Zadanie kolegi',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->other->id,
            'created_by' => $this->user->id,
        ]);

        $component = Livewire::actingAs($this->user)->test(TasksGrid::class)
            ->assertSee('Moje zadanie')
            ->assertSee('Zadanie kolegi');

        $component->set('assignedFilter', 'me')
            ->assertSee('Przypisany: Ja')
            ->assertSee('Moje zadanie')
            ->assertDontSee('Zadanie kolegi');

        $component->set('assignedFilter', (string) $this->other->id)
            ->assertSee('Przypisany: Kolega')
            ->assertDontSee('Moje zadanie')
            ->assertSee('Zadanie kolegi');

        $component->call('clearFilter', 'assignedFilter')
            ->assertSet('assignedFilter', '')
            ->assertSee('Moje zadanie')
            ->assertSee('Zadanie kolegi');
    }
}
