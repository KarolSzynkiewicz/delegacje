<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListUsersTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'list_users';

    protected string $description = <<<'MARKDOWN'
        Lista użytkowników (id, name) do `assigned_to`, `created_by`
        i @wzmianek w komentarzach.

        Opcjonalnie `q` – fragment nazwy.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $limit = (int) ($validated['limit'] ?? 100);

        $query = User::query()->orderBy('name');

        if (! empty($validated['q'])) {
            $query->where('name', 'like', '%'.$validated['q'].'%');
        }

        $total = (clone $query)->count();
        $users = $query->limit($limit)->get(['id', 'name']);

        return Response::json([
            'meta' => [
                'returned' => $users->count(),
                'total' => $total,
            ],
            'users' => $users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
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
                ->description('Fragment nazwy użytkownika.'),
            'limit' => $schema->integer()
                ->description('Maksymalna liczba rekordów. Domyślnie 100.')
                ->min(1)
                ->max(200),
        ];
    }
}
