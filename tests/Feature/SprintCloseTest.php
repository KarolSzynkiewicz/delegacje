<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\SprintBoard;
use App\Livewire\SprintLifecycleBar;
use App\Livewire\SprintsTable;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use App\Services\SprintCloseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SprintCloseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_index_is_a_grid_and_hides_closed_and_parked_by_default(): void
    {
        $active = Sprint::factory()->create([
            'name' => 'Gramy teraz',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $closed = Sprint::factory()->create([
            'name' => 'Już było',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'closed_at' => now()->subDay(),
        ]);
        $parked = Sprint::factory()->create([
            'name' => 'Odstawione',
            'parked_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('sprints.index'))
            ->assertOk()
            ->assertSeeLivewire(SprintsTable::class)
            ->assertSee('Gramy teraz')
            ->assertDontSee('Już było')
            ->assertDontSee('Odstawione');

        $this->assertSame('Aktywny', $active->statusLabel());
        $this->assertSame('Zakończony', $closed->statusLabel());
        $this->assertSame('Później', $parked->statusLabel());
        $this->assertSame('Po terminie', Sprint::factory()->create([
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ])->statusLabel());
    }

    public function test_close_unpins_open_tasks_keeps_done_and_cancelled_and_writes_comments(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'Prawda']);
        $done = $this->makeTask('Zrobione', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);
        $cancelled = $this->makeTask('Anulowane', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::CANCELLED,
        ]);
        $open = $this->makeTask('Otwarte', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::PENDING,
        ]);
        $wip = $this->makeTask('W toku', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $result = app(SprintCloseService::class)->close($sprint, $this->user, true, 'Zostawiamy kanały na później.');

        $this->assertSame(1, $result['done']);
        $this->assertSame(2, $result['unpinned']);
        $this->assertNotNull($sprint->fresh()->closed_at);
        $this->assertNull($sprint->fresh()->parked_at);
        $this->assertSame($sprint->id, $done->fresh()->sprint_id);
        $this->assertSame($sprint->id, $cancelled->fresh()->sprint_id);
        $this->assertNull($open->fresh()->sprint_id);
        $this->assertNull($wip->fresh()->sprint_id);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'sprint',
            'commentable_id' => $sprint->id,
            'body' => "Zamknięto · 1 zrobione · 2 odpięte\n\nZostawiamy kanały na później.",
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'project_task',
            'commentable_id' => $open->id,
            'body' => 'Wypadło ze sprintu Prawda → backlog',
        ]);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'project_task',
            'commentable_id' => $wip->id,
            'body' => 'Wypadło ze sprintu Prawda → backlog',
        ]);
        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'project_task',
            'commentable_id' => $done->id,
        ]);
        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'project_task',
            'commentable_id' => $cancelled->id,
        ]);
    }

    public function test_close_can_leave_open_tasks_in_the_sprint(): void
    {
        $sprint = Sprint::factory()->create();
        $open = $this->makeTask('Zostaje', [
            'sprint_id' => $sprint->id,
            'status' => TaskStatus::PENDING,
        ]);

        app(SprintCloseService::class)->close($sprint, $this->user, false);

        $this->assertSame($sprint->id, $open->fresh()->sprint_id);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'sprint',
            'commentable_id' => $sprint->id,
            'body' => 'Zamknięto · 0 zrobione · 0 odpięte',
        ]);
        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'project_task',
            'commentable_id' => $open->id,
        ]);
    }

    public function test_cannot_close_twice(): void
    {
        $sprint = Sprint::factory()->create(['closed_at' => now()]);

        $this->expectException(\InvalidArgumentException::class);
        app(SprintCloseService::class)->close($sprint, $this->user);
    }

    public function test_park_and_unpark(): void
    {
        $sprint = Sprint::factory()->create();

        app(SprintCloseService::class)->park($sprint);
        $this->assertNotNull($sprint->fresh()->parked_at);
        $this->assertSame('Później', $sprint->fresh()->statusLabel());

        app(SprintCloseService::class)->unpark($sprint->fresh());
        $this->assertNull($sprint->fresh()->parked_at);

        $closed = Sprint::factory()->create(['closed_at' => now()]);
        $this->expectException(\InvalidArgumentException::class);
        app(SprintCloseService::class)->park($closed);
    }

    public function test_sprints_table_close_action_hides_row_until_closed_filter(): void
    {
        $sprint = Sprint::factory()->create([
            'name' => 'Do zamknięcia',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);
        $this->makeTask('Otwarte', ['sprint_id' => $sprint->id, 'status' => TaskStatus::PENDING]);

        Livewire::actingAs($this->user)
            ->test(SprintsTable::class)
            ->assertSee('Do zamknięcia')
            ->call('startClose', $sprint->id)
            ->set('closeNote', 'Dość.')
            ->call('confirmClose')
            ->assertSee('został zakończony')
            ->assertSeeHtml('Rekordów: <strong>0</strong>')
            ->call('toggleStatus', Sprint::BOARD_CLOSED)
            ->assertSeeHtml('Rekordów: <strong>1</strong>')
            ->assertSee('Do zamknięcia');

        $this->assertNotNull($sprint->fresh()->closed_at);
    }

    public function test_sprint_board_can_close_from_show(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'Z tablicy']);
        $this->makeTask('Kartka', ['sprint_id' => $sprint->id]);

        Livewire::actingAs($this->user)
            ->test(SprintBoard::class, ['sprint' => $sprint])
            ->call('startClose', $sprint->id)
            ->call('confirmClose')
            ->assertSee('Sprint zakończony');

        $this->assertNotNull($sprint->fresh()->closed_at);
    }

    public function test_sprint_lifecycle_bar_can_close_from_header(): void
    {
        $sprint = Sprint::factory()->create(['name' => 'Z belki']);
        $this->makeTask('Kartka', ['sprint_id' => $sprint->id]);

        Livewire::actingAs($this->user)
            ->test(SprintLifecycleBar::class, ['sprint' => $sprint])
            ->call('startClose', $sprint->id)
            ->call('confirmClose')
            ->assertDispatched('sprint-lifecycle-changed');

        $this->assertNotNull($sprint->fresh()->closed_at);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTask(string $name, array $overrides = []): ProjectTask
    {
        return ProjectTask::query()->create(array_merge([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ], $overrides));
    }
}
