<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ParsesTaskId;
use App\Mcp\Support\ProcedurePayload;
use App\Models\ProcedureTemplate;
use App\Services\ProcedureRunService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use RuntimeException;

#[IsDestructive]
class StartProcedureTool extends Tool
{
    use ActsAsConfiguredUser;
    use ParsesTaskId;

    protected string $name = 'start_procedure';

    protected string $description = <<<'MARKDOWN'
        Uruchamia nowy przebieg procedury z templatki (`template_id`).
        Tworzy kartę zadania powiązaną z runem.

        Najpierw `list_procedure_templates` i `list_procedure_runs` z tym
        `template_id` — powiedz, czy już leci aktywny run, zanim odpalisz drugi.

        Jeśli templatka `requires_subject`, podaj `subject_id` (i ewentualnie
        `subject_type`). `name_suffix` trafia do nazwy karty („Onboarding · Jan”).

        Zasada obowiązkowa: pokaż templatkę, podmiot i czy są aktywne runy,
        poczekaj na zgodę, dopiero `confirmed_by_user: true`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = $this->actingUser();

        if (! $user->isAdmin() && ! $user->hasPermission('tasks.view')) {
            return Response::error(
                "Użytkownik {$user->name} nie ma dostępu do modułu zadań – start procedury odrzucony."
            );
        }

        $validated = $request->validate([
            'confirmed_by_user' => ['required', 'boolean'],
            'template_id' => ['required'],
            'name_suffix' => ['nullable', 'string', 'max:80'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'subject_type' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer'],
        ]);

        if ($validated['confirmed_by_user'] !== true) {
            return Response::error(
                'Start wstrzymany: brak potwierdzenia. Pokaż templatkę i ewentualne aktywne runy, '
                .'poproś o akceptację i wywołaj ponownie z confirmed_by_user=true.'
            );
        }

        $templateId = $this->parseTaskId($validated['template_id']);
        if (! $templateId) {
            return Response::error('Podaj prawidłowe `template_id`.');
        }

        $template = ProcedureTemplate::query()->find($templateId);
        if (! $template) {
            return Response::error("Nie znaleziono templatki procedury #{$templateId}.");
        }

        try {
            $run = app(ProcedureRunService::class)->startRun($template, [
                'name_suffix' => $validated['name_suffix'] ?? null,
                'assigned_to' => $validated['assigned_to'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'subject_type' => $validated['subject_type'] ?? $template->subject_type,
                'subject_id' => $validated['subject_id'] ?? null,
            ]);
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        return Response::json([
            'meta' => [
                'started_at' => now()->toIso8601String(),
                'started_by' => $user->name,
            ],
            'run' => ProcedurePayload::runDetail($run->fresh()),
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'template_id' => $schema->string()
                ->description('ID templatki z list_procedure_templates.')
                ->required(),
            'name_suffix' => $schema->string()
                ->description('Dopisek do nazwy karty, np. imię kandydata.'),
            'assigned_to' => $schema->integer()
                ->description('users.id osoby na karcie zadania.'),
            'due_date' => $schema->string()
                ->description('Termin karty YYYY-MM-DD.'),
            'subject_type' => $schema->string()
                ->description('Typ podmiotu, jeśli templatka go wymaga (vehicle, employee, …).'),
            'subject_id' => $schema->integer()
                ->description('ID podmiotu (auto, kandydat, …).'),
            'confirmed_by_user' => $schema->boolean()
                ->description('True tylko po wyraźnej zgodzie użytkownika.')
                ->required(),
        ];
    }
}
