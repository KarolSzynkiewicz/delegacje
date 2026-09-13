<?php

namespace App\Mcp\Tools;

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
class GetProcedureRunTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'get_procedure_run';

    protected string $description = <<<'MARKDOWN'
        Aktualny stan jednego przebiegu procedury: status, krok(i), opcje decyzji,
        checklista i `prompt` do przeczytania na głos.

        Wejście: `run_id`. Listę daje `list_procedure_runs`.
        Żeby przejść krok: `advance_procedure`. Żeby ruszyć z Startu: `begin: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'run_id' => ['required'],
        ]);

        $id = $this->parseTaskId($validated['run_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `run_id` (liczba albo #12).');
        }

        $run = ProcedureRun::query()->find($id);
        if (! $run) {
            return Response::error("Nie znaleziono przebiegu procedury #{$id}.");
        }

        return Response::json([
            'run' => ProcedurePayload::runDetail($run),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'run_id' => $schema->string()
                ->description('ID przebiegu (procedure_runs.id) albo "#12".')
                ->required(),
        ];
    }
}
