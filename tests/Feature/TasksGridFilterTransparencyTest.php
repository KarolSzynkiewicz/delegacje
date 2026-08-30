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
            ->assertDontSee('Dawno zakończone zadanie')
            ->assertSee('dt-card__row', false)
            ->assertSee('dt-card__label', false);

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

        $component->call('toggleType', WorkItemType::Callback->value);

        $expected = array_map(fn ($t) => $t->value, WorkItemType::cases());
        sort($expected);
        $actual = $component->get('selectedTypes');
        sort($actual);
        $this->assertSame($expected, $actual);
        $component->assertDontSee('Typ pracy:');
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

    public function test_created_by_filter_narrows_to_items_i_initiated(): void
    {
        ProjectTask::query()->create([
            'name' => 'Zainicjowane przeze mnie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->other->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Zainicjowane przez kolegę',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->other->id,
        ]);

        Livewire::actingAs($this->user)->test(TasksGrid::class)
            ->assertSee('Zainicjowane przeze mnie')
            ->assertSee('Zainicjowane przez kolegę')
            ->set('createdByFilter', 'me')
            ->assertSee('Utworzono przez: Ja')
            ->assertSee('Zainicjowane przeze mnie')
            ->assertDontSee('Zainicjowane przez kolegę')
            ->set('createdByFilter', (string) $this->other->id)
            ->assertSee('Utworzono przez: Kolega')
            ->assertDontSee('Zainicjowane przeze mnie')
            ->assertSee('Zainicjowane przez kolegę');
    }

    public function test_or_within_a_field_ands_across_fields(): void
    {
        $stranger = User::factory()->create(['name' => 'Obcy']);

        ProjectTask::query()->create([
            'name' => 'Ja oczekujące',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Kolega zakończone',
            'status' => TaskStatus::COMPLETED,
            'assigned_to' => $this->other->id,
            'created_by' => $this->other->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Ja zakończone też',
            'status' => TaskStatus::COMPLETED,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Obcy oczekujące poza',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $stranger->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Kolega w trakcie poza',
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->other->id,
            'created_by' => $this->other->id,
        ]);

        $and = Livewire::actingAs($this->user)->test(TasksGrid::class)
            ->call('clearFilters')
            ->call('setStatusBucket', 'closed')
            ->set('assignedFilter', 'me');

        // Między polami zawsze I: zamknięte ORAZ przypisane do mnie.
        $and->assertDontSee('Ja oczekujące')
            ->assertDontSee('Kolega zakończone')
            ->assertSee('Ja zakończone też');

        // Wewnątrz pola: Ja LUB Kolega, oraz oczekujące LUB zakończone.
        $and->call('toggleAssignedFilter', (string) $this->other->id)
            ->call('toggleStatusValue', TaskStatus::PENDING->value)
            ->call('toggleStatusValue', TaskStatus::CANCELLED->value)
            ->assertSee('Przypisany: Ja lub Kolega')
            ->assertSee('Status: Oczekujące lub Zakończone')
            ->assertSee('Ja oczekujące')
            ->assertSee('Kolega zakończone')
            ->assertSee('Ja zakończone też')
            ->assertDontSee('Obcy oczekujące poza')
            ->assertDontSee('Kolega w trakcie poza');
    }

    public function test_neq_status_excludes_the_selected_bucket(): void
    {
        ProjectTask::query()->create([
            'name' => 'Neq aktywne',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Neq zamknięte',
            'status' => TaskStatus::COMPLETED,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)->test(TasksGrid::class)
            ->assertSee('Neq aktywne')
            ->assertDontSee('Neq zamknięte')
            ->call('setFilterOp', 'status', 'neq')
            ->assertSee('Status: ≠ Aktywne')
            ->assertDontSee('Neq aktywne')
            ->assertSee('Neq zamknięte');
    }
}
