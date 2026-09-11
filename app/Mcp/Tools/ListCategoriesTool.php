<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\CategoryDictionary;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListCategoriesTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'list_categories';

    protected string $description = <<<'MARKDOWN'
        Słownik kategorii (zwykły tekst) z liczbą otwartych i wszystkich zadań.

        Użyj zamiast dumpa kart, gdy potrzebujesz dokładnej nazwy do
        `search_tasks.category` albo `set_task_categories`. Opcjonalnie `q`
        – fragment nazwy (np. „Bug” znajdzie „Bug / UI”).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $q = isset($validated['q']) ? trim($validated['q']) : null;

        $categories = CategoryDictionary::all($q === '' ? null : $q);

        return Response::json([
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'returned' => count($categories),
                'q' => $q === '' ? null : $q,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()
                ->description('Fragment nazwy kategorii.'),
        ];
    }
}
