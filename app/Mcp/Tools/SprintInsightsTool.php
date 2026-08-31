<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\Sprint;
use App\Services\SprintInsights;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class SprintInsightsTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'sprint_insights';

    protected string $description = <<<'MARKDOWN'
        Zdrowie sprintu – ten sam JSON co tablica sprintu: progress vs linia
        idealna, velocity, burndown, forecast, overdue, unassigned, workload,
        milestony i tekst coacha.

        Wejście: `sprint_id`. Listę sprintów daje `backlog_overview`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'sprint_id' => ['required', 'integer', 'exists:sprints,id'],
        ]);

        $sprint = Sprint::query()->find($validated['sprint_id']);
        if (! $sprint) {
            return Response::error('Nie znaleziono sprintu #'.$validated['sprint_id'].'.');
        }

        $insights = app(SprintInsights::class)->for($sprint);

        return Response::json([
            'sprint' => [
                'id' => $sprint->id,
                'name' => $sprint->name,
                'goal' => $sprint->goal,
                'definition_of_done' => $sprint->definition_of_done,
                'start_date' => $sprint->start_date?->toDateString(),
                'end_date' => $sprint->end_date?->toDateString(),
                'status' => $sprint->statusLabel(),
                'url' => route('sprints.show', $sprint),
            ],
            'insights' => $insights,
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sprint_id' => $schema->integer()
                ->description('ID sprintu (sprints.id).')
                ->required(),
        ];
    }
}
