<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\ProcedurePayload;
use App\Models\ProcedureRun;
use App\Services\ProcedureRunService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use RuntimeException;

#[IsDestructive]
class AdvanceProcedureTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'advance_procedure';

    protected string $description = <<<'MARKDOWN'
        Przesuwa przebieg procedury o jeden krok (albo wraca / porzuca).

        Najpierw `get_procedure_run` — tam jest `prompt` i `active_steps`.
        - zwykły krok / zadanie: `run_id` (node_id opcjonalny, gdy jest jeden aktywny)
        - decyzja: `edge_id` z opcji
        - checklista: `checklist` mapa id→true
        - komentarz: `comment_body`
        - utknięty na Starcie: `begin: true`
        - cofnij: `back: true`
        - porzuć: `abandon: true`

        Kroku `approval` nie da się domknąć tym narzędziem — musi zatwierdzić
        osoba z wniosku.

        Zasada obowiązkowa: przeczytaj `prompt`, potwierdź wybór, dopiero
        `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.update')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma uprawnienia tasks.update – zmiana procedury odrzucona."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'run_id' => ['required'],
            'node_id' => ['nullable', 'string', 'max:64'],
            'edge_id' => ['nullable', 'string', 'max:64'],
            'checklist' => ['nullable', 'array'],
            'comment_body' => ['nullable', 'string', 'max:2000'],
            'action_payload' => ['nullable', 'array'],
            'begin' => ['nullable', 'boolean'],
            'back' => ['nullable', 'boolean'],
            'abandon' => ['nullable', 'boolean'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Zapis wstrzymany: brak potwierdzenia. Pokaż prompt kroku i wybór, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $id = $this->parseTaskId($validated['run_id']);
        if (! $id) {
            return Response::error('Podaj prawidłowe `run_id`.');
        }

        $run = ProcedureRun::query()->find($id);
        if (! $run) {
            return Response::error("Nie znaleziono przebiegu procedury #{$id}.");
        }

        $service = app(ProcedureRunService::class);

        try {
            if ($validated['abandon'] ?? false) {
                $service->abandon($run);
            } elseif ($validated['begin'] ?? false) {
                $service->beginFromStart($run);
            } elseif ($validated['back'] ?? false) {
                $nodeId = $this->resolveNodeId($run, $validated['node_id'] ?? null);
                if ($nodeId === null) {
                    return Response::error('Podaj `node_id` kroku, z którego wracasz (albo niech będzie jeden aktywny).');
                }
                $service->goBackNode($run, $nodeId);
            } else {
                $nodeId = $this->resolveNodeId($run, $validated['node_id'] ?? null);
                if ($nodeId === null) {
                    return Response::error(
                        'Podaj `node_id`. Aktualne kroki: '.self::activeSummary($run)
                    );
                }

                $node = $run->findNodeById($nodeId);
                $type = (string) ($node['type'] ?? '');
                if ($type === 'approval') {
                    return Response::error('Ten krok czeka na zatwierdzenie — musi kliknąć zatwierdzający, nie asystent.');
                }

                $stepData = $validated['action_payload'] ?? [];
                if (filled($validated['comment_body'] ?? null)) {
                    $stepData['body'] = $validated['comment_body'];
                }
                if (is_array($validated['checklist'] ?? null)) {
                    $stepData = [];
                    foreach ($validated['checklist'] as $key => $row) {
                        if (is_array($row)) {
                            $itemId = (string) ($row['item_id'] ?? $row['id'] ?? $key);
                            $stepData[] = ['item_id' => $itemId, 'checked' => (bool) ($row['checked'] ?? true)];
                        } else {
                            $stepData[] = ['item_id' => (string) $key, 'checked' => (bool) $row];
                        }
                    }
                }

                $service->advanceNode($run, $nodeId, $validated['edge_id'] ?? null, $stepData);
            }
        } catch (ValidationException $e) {
            return Response::error($e->validator->errors()->first() ?: 'Walidacja kroku nie przeszła.');
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        $run->refresh();

        return Response::json([
            'meta' => [
                'applied_at' => now()->toIso8601String(),
                'applied_by' => $user->name,
            ],
            'run' => ProcedurePayload::runDetail($run),
        ]);
    }

    private function resolveNodeId(ProcedureRun $run, ?string $nodeId): ?string
    {
        if (filled($nodeId)) {
            return $nodeId;
        }

        $ids = $run->activeNodeIds();

        return count($ids) === 1 ? $ids[0] : null;
    }

    private static function activeSummary(ProcedureRun $run): string
    {
        $labels = collect($run->activeNodes())
            ->map(fn (array $node) => ($node['id'] ?? '?').' '.($node['name'] ?? $node['type'] ?? ''))
            ->implode(', ');

        return $labels !== '' ? $labels : 'brak';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'run_id' => $schema->string()
                ->description('ID przebiegu z get_procedure_run.')
                ->required(),
            'node_id' => $schema->string()
                ->description('ID aktywnego kroku. Można pominąć, gdy jest tylko jeden.'),
            'edge_id' => $schema->string()
                ->description('Wybór na decyzji (z active_steps.options).'),
            'checklist' => $schema->array()
                ->description('Pozycje checklisty: [{id, checked}].')
                ->items($schema->object([
                    'id' => $schema->string()->description('ID pozycji z get_procedure_run.'),
                    'checked' => $schema->boolean()->description('True = odhaczone.'),
                ])),
            'comment_body' => $schema->string()
                ->description('Treść na kroku typu comment.'),
            'action_payload' => $schema->object()
                ->description('Pola akcji domenowej na kroku action.'),
            'begin' => $schema->boolean()
                ->description('Ruszyć z Startu, gdy needs_begin.'),
            'back' => $schema->boolean()
                ->description('Cofnij ten krok.'),
            'abandon' => $schema->boolean()
                ->description('Porzuć cały przebieg.'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie na ten krok.')
                ->required(),
        ];
    }
}
