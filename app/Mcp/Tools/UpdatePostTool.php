<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\ForumPayload;
use App\Models\ForumPost;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsDestructive]
#[IsIdempotent]
class UpdatePostTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'update_post';

    protected string $description = <<<'MARKDOWN'
        Edytuje wątek tablicy: tytuł, tekst, tagi albo pinned.
        Nie rusza okładki ani obrazków w treści – tylko mięso.

        Zasada obowiązkowa: pokaż ID, co się zmieni, poczekaj na zgodę,
        dopiero wtedy `confirmed_by_user: true`.

        Edytować może autor albo admin. Puste `tags: []` zdejmuje tagi.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'post_id' => ['required'],
            'title' => ['nullable', 'string', 'max:80'],
            'body' => ['nullable', 'string', 'max:20000'],
            'tags' => ['nullable', 'array', 'max:8'],
            'tags.*' => ['required', 'string', 'max:24'],
            'pinned' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia użytkownika. Pokaż planowaną zmianę, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $id = $this->parseTaskId($validated['post_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `post_id` (liczba albo #12).');
        }

        $post = ForumPost::query()->find($id);
        if (! $post) {
            return Response::error("Nie znaleziono wątku #{$id}.");
        }

        if (! $post->canBeManagedBy($user)) {
            return Response::error(
                "Użytkownik {$user->name} nie może edytować wątku #{$post->id} (tylko autor albo admin)."
            );
        }

        $newTitle = null;
        if (array_key_exists('title', $validated) && $validated['title'] !== null) {
            $newTitle = trim($validated['title']);
            if ($newTitle === '') {
                return Response::error('Tytuł nie może być pusty.');
            }
        }

        $newBody = null;
        if (array_key_exists('body', $validated) && $validated['body'] !== null) {
            $newBody = trim($validated['body']);
            if ($newBody === '') {
                return Response::error('Treść nie może być pusta.');
            }
        }

        $hasChange = $newTitle !== null
            || $newBody !== null
            || array_key_exists('tags', $validated)
            || array_key_exists('pinned', $validated);

        if (! $hasChange) {
            return Response::error('Nic do zapisania: podaj title, body, tags albo pinned.');
        }

        $before = ForumPayload::detail($post);
        $changed = [];

        if ($newTitle !== null) {
            $post->update(['title' => $newTitle]);
            $changed[] = 'title';
        }

        if ($newBody !== null) {
            $images = collect($post->blocks())->where('type', 'image')->values()->all();
            $blocks = ForumPost::normalizeBlocks(array_merge(
                [['type' => 'text', 'content' => $newBody]],
                $images,
            ));
            if ($blocks === [] || collect($blocks)->where('type', 'text')->isEmpty()) {
                return Response::error('Treść nie może być pusta.');
            }
            $post->update(['body' => $blocks]);
            $changed[] = 'body';
        }

        if (array_key_exists('tags', $validated)) {
            $post->syncTagsFromString(implode(', ', $validated['tags'] ?? []));
            $changed[] = 'tags';
        }

        if (array_key_exists('pinned', $validated) && $validated['pinned'] !== null) {
            $post->update(['pinned' => (bool) $validated['pinned']]);
            $changed[] = 'pinned';
        }

        $post->refresh();
        $post->load(['user:id,name', 'tags:id,name,slug']);
        $post->loadCount('comments');

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
                'changed' => array_values(array_unique($changed)),
            ],
            'before' => $before,
            'post' => ForumPayload::detail($post),
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
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
            'title' => $schema->string()
                ->description('Nowy tytuł (max 80 znaków).'),
            'body' => $schema->string()
                ->description('Nowy tekst. Obrazki w wątku zostają nietknięte.'),
            'tags' => $schema->array()
                ->description('Pełna lista tagów do zapisu. Pusta tablica zdejmuje tagi.')
                ->items($schema->string()),
            'pinned' => $schema->boolean()
                ->description('Przypnij albo odepnij wątek.'),
        ];
    }
}
