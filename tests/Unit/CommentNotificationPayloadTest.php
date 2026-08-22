<?php

namespace Tests\Unit;

use App\Enums\LogisticsEventType;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\TaskAssigned;
use Tests\TestCase;

class CommentNotificationPayloadTest extends TestCase
{
    public function test_mention_on_task_links_to_comment_anchor(): void
    {
        $task = new ProjectTask(['name' => 'Pompa']);
        $task->id = 90;

        $comment = new Comment(['body' => '@robert! sprawdź uszczelkę']);
        $comment->id = 134;
        $comment->setRelation('commentable', $task);

        $author = new User(['name' => 'someone']);
        $author->id = 1;
        $robert = new User(['name' => 'robert']);
        $robert->id = 2;

        $payload = (new CommentMentioned($comment, $author))->toDatabase($robert);

        $this->assertSame(route('tasks.show', $task).'#comment-134', $payload['url']);
        $this->assertSame('Pompa', $payload['context_name']);
        $this->assertSame('@robert! sprawdź uszczelkę', $payload['excerpt']);
    }

    public function test_mention_on_departure_links_to_event_comment(): void
    {
        $event = new LogisticsEvent([
            'type' => LogisticsEventType::DEPARTURE,
            'event_date' => now()->startOfDay(),
        ]);
        $event->id = 17;

        $comment = new Comment(['body' => 'Kto zabiera klucze?']);
        $comment->id = 8;
        $comment->setRelation('commentable', $event);

        $this->assertSame(route('departures.show', $event).'#comment-8', $comment->urlWithCommentAnchor());
        $this->assertStringContainsString('Wyjazd', $comment->notificationContextLabel());
    }

    public function test_mention_on_sprint_links_to_sprint_comment(): void
    {
        $sprint = new \App\Models\Sprint(['name' => 'Sprint 12']);
        $sprint->id = 3;

        $comment = new Comment(['body' => 'Trzymamy scope.']);
        $comment->id = 44;
        $comment->setRelation('commentable', $sprint);

        $this->assertSame(route('sprints.show', $sprint).'#comment-44', $comment->urlWithCommentAnchor());
        $this->assertSame('Sprint 12', $comment->notificationContextLabel());
    }

    public function test_task_assigned_from_comment_uses_comment_url(): void
    {
        $project = new Project(['name' => 'Hala']);
        $project->id = 4;

        $comment = new Comment(['body' => '@robert! weź to']);
        $comment->id = 21;
        $comment->setRelation('commentable', $project);

        $mention = new CommentMention(['title' => 'weź to']);
        $mention->id = 55;
        $mention->setRelation('comment', $comment);

        $author = new User(['name' => 'someone']);
        $author->id = 1;
        $robert = new User(['name' => 'robert']);
        $robert->id = 2;

        $payload = (new TaskAssigned($mention, $author))->toDatabase($robert);

        $this->assertSame($comment->urlWithCommentAnchor(), $payload['task_url']);
        $this->assertSame('@robert! weź to', $payload['excerpt']);
    }
}
