<?php

namespace App\Mcp\Tools;

use App\Enums\ProcedureRunStatus;
use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Support\ProcedurePayload;
use App\Models\ProcedureTemplate;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListProcedureTemplatesTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'list_procedure_templates';

    protected string $description = <<<'MARKDOWN'
        Lista templatek procedur: nazwa, czy wymaga podmiotu (auto, kandydat…),
        ile ma aktywnych / skończonych / porzuconych runów.

        Filtr `q` szuka w nazwie i kategorii. `has_active_runs: true` zostawia
        tylko templatki z runem w trakcie.

        Potem: `list_procedure_runs` (runy jednej templatki), `start_procedure`
        (nowy run), `get_procedure_run` (aktualny krok do przejścia głosem).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'has_active_runs' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);

        $query = ProcedureTemplate::query()
            ->withCount([
                'runs as runs_in_progress_count' => fn ($q) => $q->where('status', ProcedureRunStatus::IN_PROGRESS),
                'runs as runs_finished_count' => fn ($q) => $q->where('status', ProcedureRunStatus::FINISHED),
                'runs as runs_abandoned_count' => fn ($q) => $q->where('status', ProcedureRunStatus::ABANDONED),
            ])
            ->orderBy('name');

        if (filled($validated['q'] ?? null)) {
            $term = '%'.$validated['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('category', 'like', $term);
            });
        }

        if ($validated['has_active_runs'] ?? false) {
            $query->whereHas('runs', fn ($q) => $q->where('status', ProcedureRunStatus::IN_PROGRESS));
        }

        $templates = $query->limit($limit)->get();

        return Response::json([
            'meta' => [
                'returned' => $templates->count(),
                'active_runs_total' => (int) $templates->sum('runs_in_progress_count'),
            ],
            'templates' => $templates->map(fn (ProcedureTemplate $template) => ProcedurePayload::template($template))->values()->all(),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'q' => $schema->string()
                ->description('Szukaj w nazwie lub kategorii templatki.'),
            'has_active_runs' => $schema->boolean()
                ->description('Tylko templatki, które mają run w trakcie.'),
            'limit' => $schema->integer()
                ->description('Max. liczba templatek. Domyślnie 50.')
                ->min(1)
                ->max(100),
        ];
    }
}
