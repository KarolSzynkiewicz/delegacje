<?php

namespace App\Mcp\Tools;

use App\Enums\CommentableType;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\Comment;
use App\Models\ProjectTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTaskCommentsTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'get_task_comments';

    protected string $description = <<<'MARKDOWN'
        Zwraca wątek komentarzy jednego zadania w kolejności chronologicznej
        (od najstarszego), z autorami i @wzmiankami.

        Paginacja: `after_id` = ostatnie widziane id, kolejne nowsze.
        Do streszczenia okresu najpierw weź ID z `period_analytics`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $max = config('ai_tools.max_comments_per_thread');

        $validated = $request->validate([
            'task_id' => ['required'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
            'after_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $id = $this->parseTaskId($validated['task_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `task_id` (liczba albo #12).');
        }

        $task = ProjectTask::query()->find($id);
        if (! $task) {
            return Response::error("Nie znaleziono zadania #{$id}.");
        }

        $limit = (int) ($validated['limit'] ?? min(30, $max));

        $query = Comment::query()
            ->with('user:id,name')
            ->where('commentable_type', CommentableType::PROJECT_TASK)
            ->where('commentable_id', $task->id)
            ->orderBy('id');

        if (! empty($validated['after_id'])) {
            $query->where('id', '>', (int) $validated['after_id']);
        }

        $total = Comment::query()
            ->where('commentable_type', CommentableType::PROJECT_TASK)
            ->where('commentable_id', $task->id)
            ->count();

        $comments = $query->limit($limit)->get();
        $lastId = $comments->last()?->id;

        return Response::json([
            'meta' => [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'returned' => $comments->count(),
                'total' => $total,
                'has_more' => $lastId !== null && $comments->count() === $limit
                    && Comment::query()
                        ->where('commentable_type', CommentableType::PROJECT_TASK)
                        ->where('commentable_id', $task->id)
                        ->where('id', '>', $lastId)
                        ->exists(),
                'url' => route('tasks.show', $task),
            ],
            'comments' => $comments->map(fn (Comment $comment) => TaskPayload::comment($comment))->values()->all(),
        ]);
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
            'limit' => $schema->integer()
                ->description('Ile komentarzy zwrócić. Domyślnie 30.')
                ->min(1)
                ->max(100),
            'after_id' => $schema->integer()
                ->description('Zwróć komentarze nowsze niż to id (paginacja).'),
        ];
    }
}
