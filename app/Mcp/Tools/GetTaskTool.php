<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\TaskPayload;
use App\Models\ProjectTask;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetTaskTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'get_task';

    protected string $description = <<<'MARKDOWN'
        Zwraca jedną kartę zadania: opis, status, osoby, sprint, podzadania
        i do 3 ostatnich skrótów komentarzy.

        Wejście: `task_id` (liczba albo "#12"). Pełny wątek: `get_task_comments`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'task_id' => ['required'],
        ]);

        $id = $this->parseTaskId($validated['task_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `task_id` (liczba albo #12).');
        }

        $task = ProjectTask::query()
            ->withCount('comments')
            ->find($id);

        if (! $task) {
            return Response::error("Nie znaleziono zadania #{$id}.");
        }

        return Response::json([
            'task' => TaskPayload::detail($task),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->string()
                ->description('ID zadania (liczba albo "#12").')
                ->required(),
        ];
    }
}
