<?php

namespace App\Mcp\Tools;

use App\Enums\CommentableType;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\Comment;
use App\Models\ForumPost;
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
        Dodaje komentarz do zadania (`task_id`) albo wątku tablicy (`post_id`).
        Podaj dokładnie jedno z nich. Bez załączników.

        Składnia jak w UI (można mieszać w jednym body):
        - `@Anna` – wzmianka / powiadomienie
        - `@Anna!` – zadanie z kontekstu (wzmianka w backlogu)
        - `@Anna?` – prośba o zatwierdzenie
        - `@wszyscy` – powiadom wszystkich (nie działa z `!` / `?`)
        - wniosek: `Tytuł @Anna? // opis po podwójnym slaszu`

        Zamiast (albo oprócz) tokenów w tekście można podać `mentions`:
        `{name albo user_id, kind: notify|task|approval}`.

        Zasada obowiązkowa: pokaż treść i skutki (@ / zadanie / wniosek)
        i poczekaj na zgodę. Dopiero wtedy `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'task_id' => ['nullable'],
            'post_id' => ['nullable'],
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'mentions' => ['nullable', 'array', 'max:10'],
            'mentions.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'mentions.*.name' => ['nullable', 'string', 'max:255'],
            'mentions.*.kind' => ['nullable', 'string', 'in:notify,task,approval'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż treść komentarza '
                .'(w tym @wzmianki / ! / ?), poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $hasTask = filled($validated['task_id'] ?? null);
        $hasPost = filled($validated['post_id'] ?? null);
        if ($hasTask === $hasPost) {
            return Response::error('Podaj dokładnie jedno: `task_id` albo `post_id`.');
        }

        $body = $this->applyMentionTokens(trim($validated['body']), $validated['mentions'] ?? []);
        if ($body === '') {
            return Response::error('Treść komentarza nie może być pusta.');
        }

        if ($hasTask) {
            return $this->commentOnTask($user, $validated, $body);
        }

        return $this->commentOnPost($user, $validated, $body);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function commentOnTask(User $user, array $validated, string $body): Response
    {
        if (! $user->isAdmin() && ! $user->hasPermission('tasks.view')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma dostępu do modułu zadań – komentarz odrzucony."
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

        $parent = $this->resolveParent(
            $validated['parent_id'] ?? null,
            CommentableType::PROJECT_TASK,
            $task->id,
        );
        if ($parent instanceof Response) {
            return $parent;
        }

        $comment = $task->addComment($body, $user, $parent);
        $mentionNotifiedIds = app(UserMentionService::class)->notifyCommentMentions($comment, $user);
        $this->notifyAssignee($task, $comment, $user, $mentionNotifiedIds);

        $comment = $comment->fresh('user');

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'target' => 'task',
            ],
            'comment' => TaskPayload::comment($comment),
            'effects' => TaskPayload::commentSideEffects($comment),
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'url' => route('tasks.show', $task),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function commentOnPost(User $user, array $validated, string $body): Response
    {
        $id = $this->parseTaskId($validated['post_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `post_id` (liczba albo #12).');
        }

        $post = ForumPost::query()->find($id);
        if (! $post) {
            return Response::error("Nie znaleziono wątku #{$id}.");
        }

        $parent = $this->resolveParent(
            $validated['parent_id'] ?? null,
            CommentableType::FORUM_POST,
            $post->id,
        );
        if ($parent instanceof Response) {
            return $parent;
        }

        $comment = $post->addComment($body, $user, $parent);
        app(UserMentionService::class)->notifyCommentMentions($comment, $user);

        $comment = $comment->fresh('user');

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'target' => 'post',
            ],
            'comment' => TaskPayload::comment($comment),
            'effects' => TaskPayload::commentSideEffects($comment),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'url' => route('dashboard.posts.show', $post),
            ],
        ]);
    }

    /**
     * @param  list<array{user_id?: int, name?: string, kind: string}>  $mentions
     */
    private function applyMentionTokens(string $body, array $mentions): string
    {
        foreach ($mentions as $row) {
            $target = null;
            if (! empty($row['user_id'])) {
                $target = User::query()->find((int) $row['user_id']);
            } elseif (! empty($row['name'])) {
                $target = UserMentionService::resolveUserByMentionHandle((string) $row['name']);
            }

            if (! $target) {
                continue;
            }

            $suffix = match ($row['kind'] ?? 'notify') {
                'task' => '!',
                'approval' => '?',
                default => '',
            };
            $token = '@'.$target->name.$suffix;
            $pattern = '/@'.preg_quote($target->name, '/').preg_quote($suffix, '/').'(?!\w)/ui';
            if (preg_match($pattern, $body) === 1) {
                continue;
            }

            $body = trim($body.' '.$token);
        }

        return $body;
    }

    private function resolveParent(mixed $parentId, CommentableType $type, int $commentableId): Comment|Response|null
    {
        if (empty($parentId)) {
            return null;
        }

        $parent = Comment::query()->find((int) $parentId);
        if (! $parent || $parent->commentable_type !== $type || (int) $parent->commentable_id !== $commentableId) {
            return Response::error('parent_id musi należeć do tego samego wątku komentarzy.');
        }

        return $parent;
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
                ->description('ID zadania albo "#12". Wzajemnie wyklucza się z post_id.'),
            'post_id' => $schema->string()
                ->description('ID wątku tablicy albo "#12". Wzajemnie wyklucza się z task_id.'),
            'body' => $schema->string()
                ->description('Treść. @Anna powiadamia, @Anna! robi zadanie, @Anna? wniosek o zatwierdzenie.')
                ->required(),
            'mentions' => $schema->array()
                ->description('Opcjonalne skutki: dokleja @token jeśli go nie ma w body.')
                ->items($schema->object([
                    'user_id' => $schema->integer()
                        ->description('users.id – albo podaj name.'),
                    'name' => $schema->string()
                        ->description('Nazwa użytkownika jak w @wzmiankach.'),
                    'kind' => $schema->string()
                        ->description('notify (domyślnie), task (@imię!), approval (@imię?).')
                        ->enum(['notify', 'task', 'approval']),
                ])),
            'parent_id' => $schema->integer()
                ->description('ID komentarza nadrzędnego, jeśli to odpowiedź w wątku.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika na treść i skutki.')
                ->required(),
        ];
    }
}
