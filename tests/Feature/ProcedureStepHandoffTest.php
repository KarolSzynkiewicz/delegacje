<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Models\CommentMention;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcedureStepHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected User $karol;

    protected User $mirek;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->karol = User::factory()->create(['name' => 'karol']);
        $this->mirek = User::factory()->create(['name' => 'mirek']);

        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->karol->assignRole($adminRole);
        }
    }

    public function test_advancing_to_another_users_step_comments_with_bang_mention(): void
    {
        Notification::fake();
        $this->actingAs($this->karol);

        $template = $this->linearTemplate([
            [
                'name' => 'Odbiór auta',
                'assigned_user_id' => $this->mirek->id,
                'instructions' => 'Sprawdź stan opon i zatankuj.',
            ],
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, [
            'task_name' => 'Procedura Karola',
            'assigned_to' => $this->karol->id,
        ]);

        $procedureTask = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertSame(0, $procedureTask->comments()->count());
        $this->assertSame(0, CommentMention::query()->count());

        $service->advance($run);

        $comment = $procedureTask->fresh()->comments()->first();
        $this->assertNotNull($comment);
        $this->assertSame($this->karol->id, $comment->user_id);
        $this->assertSame("@mirek! Krok: Odbiór auta\nZrób: Sprawdź stan opon i zatankuj.", $comment->body);

        $mention = CommentMention::query()
            ->where('assigned_to', $this->mirek->id)
            ->first();

        $this->assertNotNull($mention);
        $this->assertSame('Odbiór auta', $mention->title);
        $this->assertSame(WorkItemStatus::Pending, $mention->status);
        $this->assertSame($comment->id, $mention->comment_id);
        Notification::assertSentTo($this->mirek, CommentMentioned::class);
    }

    public function test_two_consecutive_steps_for_the_same_user_create_one_comment(): void
    {
        Notification::fake();
        $this->actingAs($this->karol);

        $template = $this->linearTemplate([
            ['name' => 'Krok A', 'assigned_user_id' => $this->mirek->id],
            ['name' => 'Krok B', 'assigned_user_id' => $this->mirek->id],
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, [
            'task_name' => 'Procedura Karola',
            'assigned_to' => $this->karol->id,
        ]);

        $service->advance($run);
        $service->advance($run->fresh());

        $procedureTask = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertSame(1, $procedureTask->comments()->count());
        $this->assertSame(1, CommentMention::query()->count());
        $this->assertSame("@mirek! Krok: Krok A\nZrób: Wykonaj ten krok w procedurze.", $procedureTask->comments()->first()->body);

        $mention = CommentMention::query()->first();
        $this->assertSame(WorkItemStatus::Pending, $mention->status);

        $service->advance($run->fresh());

        $this->assertSame(WorkItemStatus::Completed, $mention->fresh()->status);
    }

    public function test_going_back_does_not_create_another_comment(): void
    {
        Notification::fake();
        $this->actingAs($this->karol);

        $template = $this->linearTemplate([
            ['name' => 'Odbiór auta', 'assigned_user_id' => $this->mirek->id],
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, [
            'task_name' => 'Procedura Karola',
            'assigned_to' => $this->karol->id,
        ]);

        $service->advance($run);
        $service->goBack($run->fresh());

        $procedureTask = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertSame(1, $procedureTask->comments()->count());
        $this->assertSame(1, CommentMention::query()->count());
        $this->assertSame(
            WorkItemStatus::Pending,
            CommentMention::query()->first()->status
        );
    }

    public function test_step_assigned_to_procedure_owner_does_not_comment(): void
    {
        Notification::fake();
        $this->actingAs($this->karol);

        $template = $this->linearTemplate([
            ['name' => 'Mój krok', 'assigned_user_id' => $this->karol->id],
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, [
            'task_name' => 'Procedura Karola',
            'assigned_to' => $this->karol->id,
        ]);

        $service->advance($run);

        $procedureTask = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertSame(0, $procedureTask->comments()->count());
        $this->assertSame(0, CommentMention::query()->count());
        Notification::assertNothingSentTo($this->karol);
        Notification::assertNothingSentTo($this->mirek);
    }

    public function test_completing_the_step_closes_the_mention_task(): void
    {
        Notification::fake();
        $this->actingAs($this->karol);

        $template = $this->linearTemplate([
            [
                'name' => 'Odbiór auta',
                'assigned_user_id' => $this->mirek->id,
                'instructions' => 'Sprawdź stan opon i zatankuj.',
            ],
        ]);

        $service = app(ProcedureRunService::class);
        $run = $service->startRun($template, [
            'task_name' => 'Procedura Karola',
            'assigned_to' => $this->karol->id,
        ]);

        $service->advance($run);

        $mention = CommentMention::query()->first();
        $this->assertSame(WorkItemStatus::Pending, $mention->status);

        $service->advance($run->fresh());

        $this->assertSame(WorkItemStatus::Completed, $mention->fresh()->status);
    }

    /**
     * @param  list<array{name: string, assigned_user_id: int|null, instructions?: string, description?: string}>  $steps
     */
    private function linearTemplate(array $steps): ProcedureTemplate
    {
        $nodes = [
            ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
        ];
        $edges = [];
        $prev = 'start-1';

        foreach ($steps as $i => $step) {
            $id = 'step-'.$i;
            $nodes[] = [
                'id' => $id,
                'type' => 'task',
                'name' => $step['name'],
                'assigned_user_id' => $step['assigned_user_id'] ?? null,
                'instructions' => $step['instructions'] ?? '',
                'description' => $step['description'] ?? '',
            ];
            $edges[] = ['id' => 'e-'.$i, 'from' => $prev, 'to' => $id];
            $prev = $id;
        }

        $nodes[] = ['id' => 'end-1', 'type' => 'end', 'name' => 'Koniec'];
        $edges[] = ['id' => 'e-end', 'from' => $prev, 'to' => 'end-1'];

        return ProcedureTemplate::query()->create([
            'name' => 'Handoff test',
            'created_by' => $this->karol->id,
            'definition' => ['nodes' => $nodes, 'edges' => $edges],
        ]);
    }
}
