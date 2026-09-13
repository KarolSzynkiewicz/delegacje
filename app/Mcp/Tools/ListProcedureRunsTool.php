<?php

namespace App\Mcp\Tools;

use App\Enums\ProcedureRunStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\ProcedurePayload;
use App\Models\ProcedureRun;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListProcedureRunsTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'list_procedure_runs';

    protected string $description = <<<'MARKDOWN'
        Lista przebiegów procedur. Domyślnie tylko `in_progress`.

        `template_id` – runy jednej templatki (czy już coś leci z tego SOP-a).
        `status`: in_progress | finished | abandoned | all.

        Szczegóły aktualnego kroku i prompt głosowy: `get_procedure_run`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'template_id' => ['nullable'],
            'status' => ['nullable', 'string', 'in:in_progress,finished,abandoned,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ProcedureRun::query()
            ->with(['template:id,name', 'task:id,name,procedure_run_id', 'startedBy:id,name', 'version'])
            ->orderByDesc('id');

        if (filled($validated['template_id'] ?? null)) {
            $templateId = $this->parseTaskId($validated['template_id']);
            if (! $templateId) {
                return Response::error('Podaj prawidłowe `template_id`.');
            }
            $query->where('procedure_template_id', $templateId);
        }

        $status = $validated['status'] ?? ProcedureRunStatus::IN_PROGRESS->value;
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $limit = (int) ($validated['limit'] ?? 40);
        $runs = $query->limit($limit)->get();

        return Response::json([
            'meta' => [
                'returned' => $runs->count(),
                'status' => $status,
            ],
            'runs' => $runs->map(fn (ProcedureRun $run) => ProcedurePayload::runListItem($run))->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template_id' => $schema->string()
                ->description('Ogranicz do templatki (procedure_templates.id).'),
            'status' => $schema->string()
                ->description('in_progress (domyślnie), finished, abandoned albo all.')
                ->enum(['in_progress', 'finished', 'abandoned', 'all']),
            'limit' => $schema->integer()
                ->description('Max. liczba runów. Domyślnie 40.')
                ->min(1)
                ->max(100),
        ];
    }
}
