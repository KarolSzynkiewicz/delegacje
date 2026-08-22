<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Enums\WorkItemType;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkItem;
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

    public function test_bang_mention_creates_a_work_item_not_a_task(): void
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

        $this->assertSame(0, ProjectTask::query()->where('subject_type', 'comment')->count());

        $mention = CommentMention::query()
            ->where('comment_id', $comment->id)
            ->where('assigned_to', $robert->id)
            ->first();

        $this->assertNotNull($mention);
        $this->assertSame($this->user->id, $mention->created_by);
        $this->assertSame('sprawdź to', $mention->title);
        $this->assertSame(WorkItemStatus::Pending, $mention->status);

        $item = WorkItem::query()->where('type', WorkItemType::FollowUp)->first();
        $this->assertNotNull($item);
        $this->assertSame($mention->id, $item->source_id);
        $this->assertSame('comment_mention', $item->source_type);
        $this->assertSame($comment->urlWithCommentAnchor(), $item->openUrl());

        $card = $item->sourceCard();
        $this->assertNotNull($card);
        $this->assertSame(route('projects.show', $project), $card['url']);
        $this->assertSame($project->name, $card['label']);
        $this->assertSame('bi-folder', $card['icon']);
        $this->assertNotSame($item->openUrl(), $card['url']);

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

    public function test_bang_mention_on_vehicle_links_to_the_vehicle(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $vehicle = Vehicle::factory()->create(['registration_number' => 'WZ 1234']);

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'vehicle',
                'commentable_id' => $vehicle->id,
                'body' => '@robert! sprawdź olej',
            ])
            ->assertRedirect();

        $this->assertSame(0, ProjectTask::query()->count());

        $comment = Comment::query()->where('commentable_type', 'vehicle')->first();
        $item = WorkItem::query()->where('type', WorkItemType::FollowUp)->first();

        $this->assertNotNull($comment);
        $this->assertNotNull($item);
        $this->assertSame(route('vehicles.show', $vehicle).'#comment-'.$comment->id, $item->openUrl());

        $card = $item->sourceCard();
        $this->assertSame(route('vehicles.show', $vehicle), $card['url']);
        $this->assertSame('WZ 1234', $card['label']);
        $this->assertSame('bi-car-front', $card['icon']);
    }

    public function test_plain_mention_does_not_create_a_mention_or_task(): void
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

        $this->assertSame(0, CommentMention::query()->count());
        $this->assertSame(0, ProjectTask::query()->count());
        Notification::assertSentTo($robert, CommentMentioned::class);
        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_everyone_bang_notifies_without_creating_mentions(): void
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

        $this->assertSame(0, CommentMention::query()->count());
        $this->assertSame(0, ProjectTask::query()->count());
        Notification::assertSentTo($robert, CommentMentioned::class);
        Notification::assertNotSentTo($robert, TaskAssigned::class);
    }

    public function test_self_bang_mention_creates_a_mention_without_assign_notification(): void
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

        $this->assertDatabaseHas('comment_mentions', [
            'comment_id' => $comment->id,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'title' => 'sobie',
        ]);
        $this->assertSame(0, ProjectTask::query()->count());

        Notification::assertNotSentTo($this->user, TaskAssigned::class);
    }

    public function test_assignee_can_tick_mention_on_comment(): void
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
        $mention = $comment->mentionFor($robert->id);
        $this->assertNotNull($mention);
        $this->assertSame(WorkItemStatus::Pending, $mention->status);

        $this->actingAs($robert)
            ->from(route('projects.show', $project))
            ->post(route('comments.mention-task.toggle', $comment))
            ->assertRedirect();

        $this->assertSame(WorkItemStatus::Completed, $mention->fresh()->status);
        $this->assertSame(WorkItemStatus::Completed, WorkItem::query()->where('type', WorkItemType::FollowUp)->first()->status);

        $this->actingAs($robert)
            ->post(route('comments.mention-task.toggle', $comment))
            ->assertRedirect();

        $this->assertSame(WorkItemStatus::Pending, $mention->fresh()->status);
    }

    public function test_legacy_mention_task_url_redirects_to_the_comment(): void
    {
        $robert = User::factory()->create(['name' => 'robert']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $robert->assignRole($adminRole);
        }

        $project = Project::factory()->create(['name' => 'Hala']);
        $comment = $project->addComment('@robert! sprawdź uszczelkę', $this->user);

        $task = ProjectTask::query()->create([
            'name' => 'sprawdź uszczelkę',
            'category' => 'Komentarz',
            'status' => \App\Enums\TaskStatus::PENDING,
            'assigned_to' => $robert->id,
            'created_by' => $this->user->id,
            'subject_type' => 'comment',
            'subject_id' => $comment->id,
        ]);

        $this->actingAs($robert)
            ->get(route('tasks.show', $task))
            ->assertRedirect($comment->urlWithCommentAnchor());
    }

    public function test_other_user_cannot_tick_someone_elses_mention(): void
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
