<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Mcp\Servers\TasksServer;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\GetTaskCommentsTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListUsersTool;
use App\Mcp\Tools\PeriodAnalyticsTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SprintInsightsTool;
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
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $task->id,
            'user_id' => $this->admin->id,
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
