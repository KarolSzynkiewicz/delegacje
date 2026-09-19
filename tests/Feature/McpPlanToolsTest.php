<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Mcp\Servers\TasksServer;
use App\Mcp\Tools\CreatePlanSessionTool;
use App\Mcp\Tools\MovePlanBlockTool;
use App\Mcp\Tools\PlanOccupancyTool;
use App\Mcp\Tools\PlanQueueTool;
use App\Mcp\Tools\SchedulePlanItemTool;
use App\Mcp\Tools\UnschedulePlanBlockTool;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class McpPlanToolsTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-09-17 12:00:00'));
        $this->travelTo(Carbon::parse('2026-09-17 12:00:00'));
    }

    public function test_occupancy_lists_blocks_meetings_and_debt_ghosts(): void
    {
        $open = $this->workItem('Kompletacja Ani', $this->anna->id);
        $block = $this->block($open, '2026-09-16 10:00:00', '2026-09-16 10:30:00');

        $todayItem = $this->workItem('Dziś u Ani', $this->anna->id);
        $this->block($todayItem, '2026-09-17 09:00:00', '2026-09-17 09:30:00');

        $this->meeting('Sync Ani', $this->anna->id, '2026-09-17 14:00:00', '2026-09-17 14:30:00');

        $payload = $this->toolJson(PlanOccupancyTool::class, [
            'assignee_name' => 'Anna',
            'week' => '2026-09-14',
        ]);

        $this->assertSame($this->anna->id, $payload['meta']['calendar_user']['id']);
        $this->assertSame('2026-09-14', $payload['meta']['week_start']);
        $this->assertStringContainsString('/plan', $payload['meta']['plan_url']);
        $this->assertGreaterThanOrEqual(1, $payload['meta']['debt_event_count']);

        $wed = collect($payload['days'])->firstWhere('date', '2026-09-16');
        $this->assertTrue($wed['debt']);
        $this->assertTrue(collect($wed['timed'])->contains(
            fn (array $event) => $event['block_id'] === $block->id && $event['ghost'] === true
        ));

        $thu = collect($payload['days'])->firstWhere('date', '2026-09-17');
        $this->assertFalse($thu['debt']);
        $this->assertTrue(collect($thu['timed'])->contains(
            fn (array $event) => $event['kind'] === 'meeting' && str_contains((string) $event['title'], 'Sync Ani')
        ));

        $debtOnly = $this->toolJson(PlanOccupancyTool::class, [
            'assigned_to' => $this->anna->id,
            'debt_only' => true,
        ]);
        $this->assertTrue(collect($debtOnly['days'])->every(fn (array $day) => $day['debt'] === true));
        $this->assertFalse(collect($debtOnly['days'])->contains(fn (array $day) => $day['date'] === '2026-09-17'));
    }

    public function test_queue_is_unscheduled_open_items_for_the_calendar_user(): void
    {
        $queued = $this->workItem('Bug z kolejki', $this->anna->id, 'Bug / UI');
        $hidden = $this->workItem('Już na dziś', $this->anna->id, 'Bug / UI');
        $this->block($hidden, '2026-09-17 11:00:00', '2026-09-17 11:30:00');
        $this->workItem('Inna kategoria', $this->anna->id, 'Backend');
        $this->workItem('Karola nie widać', $this->admin->id, 'Bug / UI');

        $payload = $this->toolJson(PlanQueueTool::class, [
            'assignee_name' => 'Anna',
            'category' => 'Bug / UI',
        ]);

        $ids = collect($payload['items'])->pluck('work_item_id')->all();
        $this->assertContains($queued->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertSame(1, $payload['meta']['total_matching']);
    }

    public function test_schedule_and_move_and_unschedule_require_hitl(): void
    {
        $item = $this->workItem('Do pinu', $this->anna->id);

        TasksServer::actingAs($this->admin)
            ->tool(SchedulePlanItemTool::class, [
                'work_item_id' => $item->id,
                'starts_at' => '2026-09-17 15:00',
                'assignee_name' => 'Anna',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $this->assertDatabaseMissing('work_item_time_blocks', ['work_item_id' => $item->id]);

        $scheduled = $this->toolJson(SchedulePlanItemTool::class, [
            'work_item_id' => $item->id,
            'starts_at' => '2026-09-17 15:00',
            'ends_at' => '2026-09-17 15:45',
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);
        $this->assertSame($item->id, $scheduled['work_item_id']);

        $block = WorkItemTimeBlock::query()->where('work_item_id', $item->id)->firstOrFail();
        $this->assertSame('2026-09-17 15:00:00', $block->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 15:45:00', $block->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame($this->anna->id, $block->user_id);

        TasksServer::actingAs($this->admin)
            ->tool(MovePlanBlockTool::class, [
                'block_id' => $block->id,
                'starts_at' => '2026-09-18 10:00',
                'assignee_name' => 'Anna',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $moved = $this->toolJson(MovePlanBlockTool::class, [
            'block_id' => $block->id,
            'starts_at' => '2026-09-18 10:00',
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);
        $this->assertSame($block->id, $moved['block_id']);
        $block->refresh();
        $this->assertSame('2026-09-18 10:00:00', $block->starts_at->format('Y-m-d H:i:s'));

        $this->toolJson(UnschedulePlanBlockTool::class, [
            'block_id' => $block->id,
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);
        $this->assertDatabaseMissing('work_item_time_blocks', ['id' => $block->id]);
        $this->assertDatabaseHas('work_items', ['id' => $item->id]);
    }

    public function test_create_plan_session_bags_items_and_skips_meetings(): void
    {
        $a = $this->workItem('Alpha sesja', $this->anna->id);
        $b = $this->workItem('Beta sesja', $this->anna->id);
        $meeting = $this->meeting('Spotkanie poza sesją', $this->anna->id, '2026-09-17 16:00:00', '2026-09-17 16:30:00');
        $meetingWi = WorkItem::query()->where('source_id', $meeting->id)->firstOrFail();

        TasksServer::actingAs($this->admin)
            ->tool(CreatePlanSessionTool::class, [
                'title' => 'Dzwonienie',
                'starts_at' => '2026-09-18 13:00',
                'ends_at' => '2026-09-18 14:00',
                'work_item_ids' => [$a->id, $b->id, $meetingWi->id],
                'assignee_name' => 'Anna',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(CreatePlanSessionTool::class, [
            'title' => 'Dzwonienie',
            'starts_at' => '2026-09-18 13:00',
            'ends_at' => '2026-09-18 14:00',
            'work_item_ids' => [$a->id, $b->id, $meetingWi->id],
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('Dzwonienie', $payload['session']['title']);
        $this->assertSame(2, $payload['session']['open_count']);
        $this->assertCount(2, $payload['added']);
        $this->assertTrue(collect($payload['skipped'])->contains(
            fn (array $row) => $row['work_item_id'] === $meetingWi->id
        ));

        $session = WorkItemTimeBlock::query()->findOrFail($payload['session']['block_id']);
        $this->assertTrue($session->isSession());
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $session->items()->pluck('work_items.id')->all());
    }

    public function test_move_meeting_uses_work_item_id(): void
    {
        $meeting = $this->meeting('Przesuń sync', $this->anna->id, '2026-09-17 08:00:00', '2026-09-17 08:30:00');
        $item = WorkItem::query()->where('source_id', $meeting->id)->firstOrFail();

        $this->toolJson(MovePlanBlockTool::class, [
            'work_item_id' => $item->id,
            'starts_at' => '2026-09-17 11:00',
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);

        $meeting->refresh();
        $this->assertSame('2026-09-17 11:00:00', $meeting->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-17 11:30:00', $meeting->ends_at->format('Y-m-d H:i:s'));

        $this->toolJson(UnschedulePlanBlockTool::class, [
            'work_item_id' => $item->id,
            'assignee_name' => 'Anna',
            'confirmed_by_user' => true,
        ]);
        $meeting->refresh();
        $this->assertNull($meeting->starts_at);
        $this->assertNull($meeting->ends_at);
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

        $this->assertIsArray($decoded, is_string($text) ? $text : json_encode($text));

        return $decoded;
    }

    private function workItem(string $name, int $assigneeId, ?string $category = null): WorkItem
    {
        $task = ProjectTask::query()->create([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'category' => $category,
            'assigned_to' => $assigneeId,
            'created_by' => $this->admin->id,
        ]);

        return WorkItem::query()->where('source_type', 'project_task')->where('source_id', $task->id)->firstOrFail();
    }

    private function block(WorkItem $item, string $starts, string $ends): WorkItemTimeBlock
    {
        return WorkItemTimeBlock::query()->create([
            'work_item_id' => $item->id,
            'kind' => WorkItemTimeBlockKind::Item,
            'user_id' => $item->assignee_id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function meeting(string $name, int $assigneeId, string $starts, string $ends): ProjectTask
    {
        return ProjectTask::query()->create([
            'name' => $name,
            'status' => TaskStatus::PENDING,
            'assigned_to' => $assigneeId,
            'created_by' => $this->admin->id,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'due_date' => Carbon::parse($starts)->toDateString(),
            'participant_ids' => [$assigneeId],
        ]);
    }
}
