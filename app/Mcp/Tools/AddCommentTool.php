<?php

namespace App\Mcp\Tools;

use App\Enums\CommentableType;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\TaskCommentAdded;
use App\Services\UserMentionService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class AddCommentTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'add_comment';

    protected string $description = <<<'MARKDOWN'
        Dodaje komentarz do zadania. `@Nazwa` wysyła powiadomienie, `@Nazwa!`
        tworzy follow-up w backlogu – tak samo jak w aplikacji.

        Zasada obowiązkowa: pokaż treść (w tym @wzmiankę do ownera) i poczekaj
        na zgodę. Dopiero wtedy wywołaj z `confirmed_by_user: true`.

        Do naganienia stale: `@{assignee} proszę o krótki update statusu.`
        Jedno wywołanie = jeden komentarz na jednym zadaniu.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.view')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma dostępu do modułu zadań – komentarz odrzucony."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'task_id' => ['required'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż treść komentarza, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $id = $this->parseTaskId($validated['task_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `task_id` (liczba albo #12).');
        }

        $task = ProjectTask::query()->find($id);
        if (! $task) {
            return Response::error("Nie znaleziono zadania #{$id}.");
        }

        $body = trim($validated['body']);
        if ($body === '') {
            return Response::error('Treść komentarza nie może być pusta.');
        }

        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = Comment::query()->find((int) $validated['parent_id']);
            if (! $parent || $parent->commentable_type !== CommentableType::PROJECT_TASK
                || (int) $parent->commentable_id !== $task->id) {
                return Response::error('parent_id musi należeć do tego samego zadania.');
            }
        }

        $comment = $task->addComment($body, $user, $parent);
        $mentionNotifiedIds = app(UserMentionService::class)->notifyCommentMentions($comment, $user);
        $this->notifyAssignee($task, $comment, $user, $mentionNotifiedIds);

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
            ],
            'comment' => TaskPayload::comment($comment->fresh('user')),
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'url' => route('tasks.show', $task),
            ],
        ]);
    }

    /**
     * @param  list<int>  $mentionNotifiedIds
     */
    private function notifyAssignee(ProjectTask $task, Comment $comment, User $author, array $mentionNotifiedIds): void
    {
        $assigneeId = $task->assigned_to;
        if (! $assigneeId || $assigneeId === $author->id) {
            return;
        }

        if (in_array($assigneeId, $mentionNotifiedIds, true)) {
            return;
        }

        $assignee = User::query()->find($assigneeId);
        $assignee?->notify(new TaskCommentAdded($task, $comment, $author));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()
                ->description('ID zadania albo "#12".')
                ->required(),
            'body' => $schema->string()
                ->description('Treść komentarza. @Nazwa powiadamia, @Nazwa! tworzy follow-up.')
                ->required(),
            'parent_id' => $schema->integer()
                ->description('ID komentarza nadrzędnego, jeśli to odpowiedź w wątku.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika na treść.')
                ->required(),
        ];
    }
}
