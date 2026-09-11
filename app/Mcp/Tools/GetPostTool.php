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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetPostTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'get_post';

    protected string $description = <<<'MARKDOWN'
        Zwraca jeden wątek tablicy: tytuł, czysty tekst (bez obrazków),
        tagi i do 3 ostatnich skrótów komentarzy.

        Wejście: `post_id` (liczba albo "#12"). Pełny wątek komentarzy:
        `get_post_comments`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'post_id' => ['required'],
        ]);

        $id = $this->parseTaskId($validated['post_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `post_id` (liczba albo #12).');
        }

        $post = ForumPost::query()
            ->withCount('comments')
            ->find($id);

        if (! $post) {
            return Response::error("Nie znaleziono wątku #{$id}.");
        }

        return Response::json([
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
                ->description('ID wątku (liczba albo "#12").')
                ->required(),
        ];
    }
}
