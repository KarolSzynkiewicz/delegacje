<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Mcp\Servers\TasksServer;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AddSubtasksTool;
use App\Mcp\Tools\CreateSprintTool;
use App\Mcp\Tools\GetTaskCommentsTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListCategoriesTool;
use App\Mcp\Tools\ListUsersTool;
use App\Mcp\Tools\PeriodAnalyticsTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SprintInsightsTool;
use App\Mcp\Tools\UpdateSubtaskTool;
use App\Mcp\Tools\UpdateTaskTool;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\TaskSubtask;
use App\Models\TaskSubtaskEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class McpTaskToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $anna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create(['name' => 'Karol']);
        $this->admin->assignRole(Role::where('name', 'administrator')->first());

        $this->anna = User::factory()->create(['name' => 'Anna']);
    }

    public function test_search_tasks_filters_by_assignee_name_and_category(): void
    {
        $annaTask = ProjectTask::query()->create([
            'name' => 'Formularz logowania',
            'status' => TaskStatus::PENDING,
            'category' => 'Bug / UI',
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Inny bug Ani ale inna kategoria',
            'status' => TaskStatus::PENDING,
            'category' => 'Backend',
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Task Karola w Bug / UI',
            'status' => TaskStatus::PENDING,
            'category' => 'Bug / UI',
            'assigned_to' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);

        $payload = $this->toolJson(SearchTasksTool::class, [
            'assignee_name' => 'Anna',
            'category' => 'Bug / UI',
        ]);

        $this->assertSame(1, $payload['meta']['total_matching']);
        $this->assertSame($annaTask->id, $payload['tasks'][0]['id']);
        $this->assertSame('Anna', $payload['tasks'][0]['assigned_to']['name']);
        $this->assertArrayNotHasKey('description', $payload['tasks'][0]);
    }

    public function test_search_tasks_finds_unassigned_and_missing_category(): void
    {
        ProjectTask::query()->create([
            'name' => 'Bez osoby i kategorii',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->admin->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'Przypisane z kategorią',
            'status' => TaskStatus::PENDING,
            'category' => 'dom',
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);

        $payload = $this->toolJson(SearchTasksTool::class, [
            'unassigned' => true,
            'missing_category' => true,
        ]);

        $this->assertSame(1, $payload['meta']['total_matching']);
        $this->assertNull($payload['tasks'][0]['assigned_to']);
        $this->assertNull($payload['tasks'][0]['category']);
    }

    public function test_get_task_and_comments_return_human_content(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Rotacje na sierpień',
            'description' => 'Ułożyć grafiki wyjazdów.',
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);

        $task->addComment('Trzeba dograć kwatery w DE.', $this->admin);
        $task->addComment('@Anna daj znać po telefonie z ekipą.', $this->admin);

        $card = $this->toolJson(GetTaskTool::class, ['task_id' => '#'.$task->id]);
        $this->assertSame('Ułożyć grafiki wyjazdów.', $card['task']['description']);
        $this->assertCount(2, $card['task']['recent_comments']);

        $thread = $this->toolJson(GetTaskCommentsTool::class, ['task_id' => $task->id]);
        $this->assertSame(2, $thread['meta']['total']);
        $this->assertStringContainsString('kwatery', $thread['comments'][0]['body']);
        $this->assertSame('Anna', $thread['comments'][1]['mentions'][0]['resolved_user']['name']);
    }

    public function test_period_analytics_points_to_hottest_and_collaboration(): void
    {
        $hot = ProjectTask::query()->create([
            'name' => 'Dyskusyjny temat',
            'status' => TaskStatus::IN_PROGRESS,
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);
        $hot->addComment('Pierwsza uwaga', $this->admin);
        $hot->addComment('Odpowiedź', $this->anna);

        $quiet = ProjectTask::query()->create([
            'name' => 'Ciche zadanie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->admin->id,
            'created_by' => $this->admin->id,
        ]);
        DB::table('project_tasks')->where('id', $quiet->id)->update([
            'updated_at' => now()->subDays(10),
        ]);

        $subtask = TaskSubtask::query()->create([
            'task_id' => $hot->id,
            'name' => 'Krok Ani',
            'created_by' => $this->admin->id,
        ]);
        TaskSubtaskEvent::log($subtask, 'completed', $this->admin->id);

        $payload = $this->toolJson(PeriodAnalyticsTool::class, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'stale_days' => 7,
        ]);

        $this->assertGreaterThanOrEqual(2, $payload['kpis']['tasks']);
        $this->assertContains($hot->id, $payload['pointers']['hottest_task_ids']);
        $this->assertContains($quiet->id, $payload['pointers']['stale_task_ids']);
        $this->assertNotEmpty($payload['collaboration']['comments']);
        $this->assertSame(
            $this->admin->name,
            $payload['collaboration']['subtask_help'][0]['helper'] ?? null
        );
        $this->assertArrayNotHasKey('tasks', $payload);
    }

    public function test_create_sprint_requires_hitl_then_reuses_identical_payload(): void
    {
        $payload = [
            'name' => 'Sprint MCP',
            'goal' => 'Ogarnąć backlog',
            'definition_of_done' => 'Na produkcji',
            'start_date' => '2026-08-24',
            'end_date' => '2026-09-06',
        ];

        TasksServer::actingAs($this->admin)
            ->tool(CreateSprintTool::class, $payload + ['confirmed_by_user' => false])
            ->assertHasErrors(['potwierdzenia']);

        $this->assertDatabaseMissing('sprints', ['name' => 'Sprint MCP']);

        $first = $this->toolJson(CreateSprintTool::class, $payload + ['confirmed_by_user' => true]);
        $second = $this->toolJson(CreateSprintTool::class, $payload + ['confirmed_by_user' => true]);

        $this->assertFalse($first['meta']['reused']);
        $this->assertTrue($second['meta']['reused']);
        $this->assertSame($first['sprint']['id'], $second['sprint']['id']);
        $this->assertSame(1, Sprint::query()->where('name', 'Sprint MCP')->count());
    }

    public function test_sprint_insights_and_list_users(): void
    {
        $sprint = Sprint::factory()->create([
            'name' => 'Sprint testowy',
            'created_by' => $this->admin->id,
        ]);

        ProjectTask::query()->create([
            'name' => 'W sprincie',
            'status' => TaskStatus::PENDING,
            'sprint_id' => $sprint->id,
            'created_by' => $this->admin->id,
        ]);

        $insights = $this->toolJson(SprintInsightsTool::class, ['sprint_id' => $sprint->id]);
        $this->assertSame('Sprint testowy', $insights['sprint']['name']);
        $this->assertArrayHasKey('velocity', $insights['insights']);
        $this->assertArrayHasKey('burndown', $insights['insights']);

        $users = $this->toolJson(ListUsersTool::class, ['q' => 'Ann']);
        $this->assertSame('Anna', $users['users'][0]['name']);
        $this->assertSame($this->anna->id, $users['users'][0]['id']);
    }

    public function test_update_task_requires_hitl_then_assigns(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Do przypisania',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->admin->id,
        ]);

        TasksServer::actingAs($this->admin)
            ->tool(UpdateTaskTool::class, [
                'task_id' => $task->id,
                'assigned_to' => $this->anna->id,
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(UpdateTaskTool::class, [
            'task_id' => $task->id,
            'assigned_to' => $this->anna->id,
            'status' => 'in_progress',
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('in_progress', $payload['task']['status']);
        $this->assertSame('Anna', $payload['task']['assigned_to']['name']);
        $this->assertSame(TaskStatus::IN_PROGRESS, $task->fresh()->status);
    }

    public function test_update_task_renames_and_unassigns_sprint(): void
    {
        $sprint = Sprint::factory()->create([
            'name' => 'Sprint do odpięcia',
            'created_by' => $this->admin->id,
        ]);

        $task = ProjectTask::query()->create([
            'name' => 'Stara nazwa',
            'description' => 'Stary opis',
            'status' => TaskStatus::PENDING,
            'sprint_id' => $sprint->id,
            'sprint_position' => 1,
            'created_by' => $this->admin->id,
        ]);

        TasksServer::actingAs($this->admin)
            ->tool(UpdateTaskTool::class, [
                'task_id' => '#'.$task->id,
                'name' => 'Nowa nazwa',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(UpdateTaskTool::class, [
            'task_id' => '#'.$task->id,
            'name' => 'Nowa nazwa',
            'description' => 'Nowy opis',
            'unassign_sprint' => true,
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('Nowa nazwa', $payload['task']['name']);
        $this->assertSame('Nowy opis', $payload['task']['description']);
        $this->assertNull($payload['task']['sprint']);
        $this->assertContains('name', $payload['meta']['changed']);
        $this->assertContains('sprint', $payload['meta']['changed']);

        $fresh = $task->fresh();
        $this->assertSame('Nowa nazwa', $fresh->name);
        $this->assertSame('Nowy opis', $fresh->description);
        $this->assertNull($fresh->sprint_id);
        $this->assertNull($fresh->sprint_position);
    }

    public function test_update_subtask_requires_hitl_then_completes_without_closing_parent(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Rodzic',
            'status' => TaskStatus::IN_PROGRESS,
            'created_by' => $this->admin->id,
        ]);

        $subtask = TaskSubtask::query()->create([
            'task_id' => $task->id,
            'name' => 'Krok 1',
            'is_completed' => false,
            'created_by' => $this->admin->id,
        ]);

        TasksServer::actingAs($this->admin)
            ->tool(UpdateSubtaskTool::class, [
                'subtask_id' => $subtask->id,
                'is_completed' => true,
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(UpdateSubtaskTool::class, [
            'subtask_id' => '#'.$subtask->id,
            'is_completed' => true,
            'assigned_to' => $this->anna->id,
            'confirmed_by_user' => true,
        ]);

        $this->assertTrue($payload['subtask']['is_completed']);
        $this->assertSame('Anna', $payload['subtask']['assigned_to']['name']);
        $this->assertSame($task->id, $payload['task']['id']);
        $this->assertTrue($subtask->fresh()->is_completed);
        $this->assertSame(TaskStatus::IN_PROGRESS, $task->fresh()->status);
        $this->assertDatabaseHas('task_subtask_events', [
            'subtask_id' => $subtask->id,
            'event' => 'completed',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_add_subtasks_requires_hitl_then_appends(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Z checklistą',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->admin->id,
        ]);

        TaskSubtask::query()->create([
            'task_id' => $task->id,
            'sort_order' => 1,
            'name' => 'Istniejący krok',
            'created_by' => $this->admin->id,
        ]);

        TasksServer::actingAs($this->admin)
            ->tool(AddSubtasksTool::class, [
                'task_id' => $task->id,
                'subtasks' => ['Nowy krok'],
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(AddSubtasksTool::class, [
            'task_id' => '#'.$task->id,
            'subtasks' => ['Nowy krok', 'Drugi nowy'],
            'confirmed_by_user' => true,
        ]);

        $this->assertSame(2, $payload['meta']['added']);
        $this->assertCount(3, $payload['subtasks']);
        $this->assertSame('Nowy krok', $payload['added'][0]['name']);
        $this->assertFalse($payload['added'][0]['is_completed']);
        $this->assertSame(2, $payload['added'][0]['sort_order']);
        $this->assertDatabaseCount('task_subtasks', 3);
        $this->assertSame('Istniejący krok', $task->fresh()->subtasks->sortBy('sort_order')->first()->name);
    }

    public function test_list_categories_returns_dictionary_without_task_cards(): void
    {
        ProjectTask::query()->create([
            'name' => 'Otwarty bug',
            'status' => TaskStatus::PENDING,
            'category' => 'Bug / UI',
            'created_by' => $this->admin->id,
        ]);
        ProjectTask::query()->create([
            'name' => 'Zamknięty bug',
            'status' => TaskStatus::COMPLETED,
            'category' => 'Bug / UI',
            'created_by' => $this->admin->id,
        ]);
        ProjectTask::query()->create([
            'name' => 'Backend',
            'status' => TaskStatus::IN_PROGRESS,
            'category' => 'Backend',
            'created_by' => $this->admin->id,
        ]);

        $payload = $this->toolJson(ListCategoriesTool::class, ['q' => 'Bug']);

        $this->assertSame(1, $payload['meta']['returned']);
        $this->assertSame('Bug / UI', $payload['categories'][0]['category']);
        $this->assertSame(1, $payload['categories'][0]['open_tasks']);
        $this->assertSame(2, $payload['categories'][0]['tasks']);
        $this->assertArrayNotHasKey('tasks', $payload);
    }

    public function test_add_comment_requires_hitl_then_notifies_assignee(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Martwe zadanie',
            'status' => TaskStatus::PENDING,
            'assigned_to' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);

        TasksServer::actingAs($this->admin)
            ->tool(AddCommentTool::class, [
                'task_id' => $task->id,
                'body' => '@Anna proszę o krótki update statusu.',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(AddCommentTool::class, [
            'task_id' => $task->id,
            'body' => '@Anna proszę o krótki update statusu.',
            'confirmed_by_user' => true,
        ]);

        $this->assertStringContainsString('update statusu', $payload['comment']['body']);
        $this->assertSame('notify', $payload['comment']['mentions'][0]['kind']);
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $task->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_add_comment_creates_task_mention_and_approval_from_native_syntax(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Kontekst',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->admin->id,
        ]);

        $taskPayload = $this->toolJson(AddCommentTool::class, [
            'task_id' => '#'.$task->id,
            'body' => 'Ogarnij grafiki',
            'mentions' => [
                ['name' => 'Anna', 'kind' => 'task'],
            ],
            'confirmed_by_user' => true,
        ]);

        $this->assertStringContainsString('@Anna!', $taskPayload['comment']['body']);
        $this->assertSame('task', $taskPayload['comment']['mentions'][0]['kind']);
        $this->assertNotEmpty($taskPayload['effects']['task_mentions']);
        $this->assertSame('Anna', $taskPayload['effects']['task_mentions'][0]['assigned_to']['name']);

        $approvalPayload = $this->toolJson(AddCommentTool::class, [
            'task_id' => $task->id,
            'body' => 'Urlop na piątek @Anna? // sprawdź obsadę',
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('approval', $approvalPayload['comment']['mentions'][0]['kind']);
        $this->assertSame('Anna', $approvalPayload['effects']['approval_requests'][0]['approver']['name']);
        $this->assertSame('Urlop na piątek', $approvalPayload['effects']['approval_requests'][0]['name']);
        $this->assertDatabaseHas('approval_requests', [
            'approver_id' => $this->anna->id,
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * @param  class-string  $tool
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function toolJson(string $tool, array $arguments): array
    {
        $response = TasksServer::actingAs($this->admin)->tool($tool, $arguments);
        $response->assertOk();

        $text = (fn (): array => $this->content())->call($response)[0] ?? '';
        $decoded = json_decode($text, true);

        $this->assertIsArray($decoded, $text);

        return $decoded;
    }
}
