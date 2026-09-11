<?php

namespace App\Mcp\Tools;

use App\Enums\CommentableType;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\Comment;
use App\Models\ForumPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetPostCommentsTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'get_post_comments';

    protected string $description = <<<'MARKDOWN'
        Zwraca wątek komentarzy jednego posta na tablicy, od najstarszego,
        z autorami i @wzmiankami (`notify` / `task` / `approval`).

        Paginacja: `after_id` = ostatnie widziane id. Bez załączników.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $max = config('ai_tools.max_comments_per_thread');

        $validated = $request->validate([
            'post_id' => ['required'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
            'after_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $id = $this->parseTaskId($validated['post_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `post_id` (liczba albo #12).');
        }

        $post = ForumPost::query()->find($id);
        if (! $post) {
            return Response::error("Nie znaleziono wątku #{$id}.");
        }

        $limit = (int) ($validated['limit'] ?? min(30, $max));

        $query = Comment::query()
            ->with('user:id,name')
            ->where('commentable_type', CommentableType::FORUM_POST->value)
            ->where('commentable_id', $post->id)
            ->orderBy('id');

        if (! empty($validated['after_id'])) {
            $query->where('id', '>', (int) $validated['after_id']);
        }

        $total = Comment::query()
            ->where('commentable_type', CommentableType::FORUM_POST->value)
            ->where('commentable_id', $post->id)
            ->count();

        $comments = $query->limit($limit)->get();
        $lastId = $comments->last()?->id;

        return Response::json([
            'meta' => [
                'post_id' => $post->id,
                'post_title' => $post->title,
                'returned' => $comments->count(),
                'total' => $total,
                'has_more' => $lastId !== null && $comments->count() === $limit
                    && Comment::query()
                        ->where('commentable_type', CommentableType::FORUM_POST->value)
                        ->where('commentable_id', $post->id)
                        ->where('id', '>', $lastId)
                        ->exists(),
                'url' => route('dashboard.posts.show', $post),
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
            'post_id' => $schema->string()
                ->description('ID wątku albo "#12".')
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
