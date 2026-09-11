<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\ForumTag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListPostTagsTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'list_post_tags';

    protected string $description = <<<'MARKDOWN'
        Słownik tagów tablicy (name, slug, liczba wątków) – bez kart postów.

        Użyj przed `search_posts` z `tag`, albo przy `create_post` /
        `update_post`, żeby nie mnożyć synonimów.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:64'],
        ]);

        $q = isset($validated['q']) ? trim($validated['q']) : '';

        $query = ForumTag::query()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('name');

        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        }

        $tags = $query->get();

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => $tags->count(),
                'q' => $q !== '' ? $q : null,
            ],
            'tags' => $tags->map(fn (ForumTag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'posts' => (int) $tag->posts_count,
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()
                ->description('Fragment nazwy albo sluga tagu.'),
        ];
    }
}
