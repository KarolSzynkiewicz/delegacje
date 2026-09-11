<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\ForumPayload;
use App\Models\ForumPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class CreatePostTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'create_post';

    protected string $description = <<<'MARKDOWN'
        Publikuje wątek na tablicy. Tylko tytuł, tekst i tagi – bez okładki
        i bez obrazków w treści.

        Zasada obowiązkowa: pokaż tytuł, treść i tagi, poczekaj na zgodę,
        dopiero wtedy `confirmed_by_user: true`.

        Tagi: tablica nazw (max 8). Słownik: `list_post_tags`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:20000'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['required', 'string', 'max:24'],
            'pinned' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż tytuł, treść i tagi, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $title = trim($validated['title']);
        $body = trim($validated['body']);
        if ($title === '' || $body === '') {
            return Response::error('Tytuł i treść nie mogą być puste.');
        }

        $blocks = ForumPost::normalizeBlocks([
            ['type' => 'text', 'content' => $body],
        ]);
        if ($blocks === []) {
            return Response::error('Treść wątku jest pusta.');
        }

        $post = ForumPost::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $blocks,
            'pinned' => (bool) ($validated['pinned'] ?? false),
        ]);
        $post->syncTagsFromString(implode(', ', $validated['tags'] ?? []));
        $post->load(['user:id,name', 'tags:id,name,slug']);

        return Response::json([
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'created_by' => $user->name,
            ],
            'post' => ForumPayload::detail($post),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('Tytuł wątku (max 80 znaków).')
                ->required(),
            'body' => $schema->string()
                ->description('Treść – sam tekst, bez załączników.')
                ->required(),
            'tags' => $schema->array()
                ->description('Nazwy tagów (max 8).')
                ->items($schema->string()),
            'pinned' => $schema->boolean()
                ->description('Czy przypiąć wątek na górze tablicy.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
