<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentMentionTaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'karol']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_bang_mention_creates_task_and_notifies_assignee(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert! sprawdź to',
            ])
            ->assertRedirect();

        $comment = Comment::query()->where('commentable_id', $project->id)->first();
        $this->assertNotNull($comment);

        $task = ProjectTask::query()
            ->where('subject_type', 'comment')
            ->where('subject_id', $comment->id)
            ->first();

        $this->assertNotNull($task);
        $this->assertSame($robert->id, $task->assigned_to);
        $this->assertSame($this->user->id, $task->created_by);
        $this->assertSame('Komentarz', $task->category);
        $this->assertSame('Wzmianka od karol', $task->name);
        $this->assertStringContainsString('@robert! sprawdź to', (string) $task->description);
        $this->assertStringContainsString('#comment-'.$comment->id, (string) $task->description);

        $card = $task->sourceCard();
        $this->assertSame($comment->urlWithCommentAnchor(), $card['url']);
        $this->assertSame('Komentarz', $card['label']);

        Notification::assertSentTo($robert, CommentMentioned::class, function (CommentMentioned $notification) use ($robert, $comment, $project): bool {
            $data = $notification->toDatabase($robert);

            return $data['url'] === $comment->fresh()->urlWithCommentAnchor()
                && $data['context_name'] === $project->name
                && str_contains((string) $data['excerpt'], 'sprawdź to');
        });
        Notification::assertSentTo($robert, TaskAssigned::class, function (TaskAssigned $notification) use ($robert, $comment): bool {
            $data = $notification->toDatabase($robert);

            return $data['task_url'] === $comment->fresh()->urlWithCommentAnchor()
                && str_contains((string) $data['excerpt'], 'sprawdź to');
        });
    }

    public function test_plain_mention_does_not_create_a_task(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert sprawdź to',
            ])
            ->assertRedirect();

        $this->assertSame(0, ProjectTask::query()->where('category', 'Komentarz')->count());
        Notification::assertSentTo($robert, CommentMentioned::class);
        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_everyone_bang_notifies_without_creating_tasks(): void
    {
        Notification::fake();

        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@wszyscy! pilne',
            ])
            ->assertRedirect();

        $this->assertSame(0, ProjectTask::query()->where('category', 'Komentarz')->count());
        Notification::assertSentTo($robert, CommentMentioned::class);
        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_self_bang_mention_creates_a_task_without_assign_notification(): void
    {
        Notification::fake();

        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@karol! sobie',
            ])
            ->assertRedirect();

        $comment = Comment::query()->where('commentable_id', $project->id)->first();
        $this->assertNotNull($comment);

        $this->assertDatabaseHas('project_tasks', [
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'category' => 'Komentarz',
            'subject_type' => 'comment',
            'subject_id' => $comment->id,
        ]);

        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_assignee_can_tick_mention_task_on_comment(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert! sprawdź to',
            ])
            ->assertRedirect();

        $comment = Comment::query()->where('commentable_id', $project->id)->first();
        $this->assertNotNull($comment);
        $task = $comment->tasks()->first();
        $this->assertNotNull($task);
        $this->assertSame(\App\Enums\TaskStatus::PENDING, $task->status);

        $this->actingAs($robert)
            ->from(route('projects.show', $project))
            ->post(route('comments.mention-task.toggle', $comment))
            ->assertRedirect();

        $this->assertSame(\App\Enums\TaskStatus::COMPLETED, $task->fresh()->status);

        $this->actingAs($robert)
            ->post(route('comments.mention-task.toggle', $comment))
            ->assertRedirect();

        $this->assertSame(\App\Enums\TaskStatus::PENDING, $task->fresh()->status);
    }

    public function test_other_user_cannot_tick_someone_elses_mention_task(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $project = Project::factory()->create();

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'project',
                'commentable_id' => $project->id,
                'body' => '@robert! sprawdź to',
            ])
            ->assertRedirect();

        $comment = Comment::query()->where('commentable_id', $project->id)->first();

        $this->actingAs($this->user)
            ->post(route('comments.mention-task.toggle', $comment))
            ->assertNotFound();
    }
}
