<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\ForumPayload;
use App\Models\ForumPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SearchPostsTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'search_posts';

    protected string $description = <<<'MARKDOWN'
        Szuka wątków na tablicy (`/dashboard`). Zwraca karty: tytuł, excerpt,
        tagi, autor – bez obrazków i bez pełnego body.

        Filtry: `q` (tytuł / treść / tag), `tag` (nazwa albo slug).
        Pełny tekst: `get_post`. Komentarze: `get_post_comments`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $max = config('ai_tools.max_search_results');

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', "max:{$max}"],
        ]);

        $limit = (int) ($validated['limit'] ?? 30);
        $q = isset($validated['q']) ? trim($validated['q']) : '';
        $tag = isset($validated['tag']) ? trim($validated['tag']) : '';

        $query = ForumPost::query()
            ->with(['user:id,name', 'tags:id,name,slug'])
            ->withCount('comments')
            ->search($q !== '' ? $q : null);

        if ($tag !== '') {
            $slug = Str::slug($tag);
            $query->whereHas('tags', function ($tags) use ($tag, $slug) {
                $tags->where(function ($inner) use ($tag, $slug) {
                    $inner->where('slug', $slug)
                        ->orWhereRaw('LOWER(name) = LOWER(?)', [$tag]);
                });
            });
        }

        $total = (clone $query)->count();

        $posts = $query
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $posts->count(),
                'total_matching' => $total,
                'filters' => [
                    'q' => $q !== '' ? $q : null,
                    'tag' => $tag !== '' ? $tag : null,
                ],
            ],
            'posts' => $posts->map(fn (ForumPost $post) => ForumPayload::listItem($post))->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()
                ->description('Fragment tytułu, treści albo tagu.'),
            'tag' => $schema->string()
                ->description('Dokładna nazwa tagu albo slug (np. logistyka).'),
            'limit' => $schema->integer()
                ->description('Maksymalna liczba kart. Domyślnie 30.')
                ->min(1)
                ->max(200),
        ];
    }
}
