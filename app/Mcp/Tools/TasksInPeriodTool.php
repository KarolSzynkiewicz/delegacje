<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Services\PromptEngine\TaskPromptBundleService;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class TasksInPeriodTool extends Tool
{
    use ActsAsConfiguredUser;

    protected string $name = 'tasks_in_period';

    protected string $description = <<<'MARKDOWN'
        Zwraca komplet zadań z podanego okresu wraz z podzadaniami, komentarzami
        i wzmiankami – gotowy kontekst do podsumowania tygodnia lub raportu.

        Zadanie trafia do wyniku, gdy w okresie zostało utworzone, zmienione,
        zakończone, ma w nim termin albo dostało komentarz lub ruch na podzadaniu.

        Użyj `period` dla typowych zakresów albo podaj własne `start_date` i `end_date`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:this_week,last_week,last_7_days,this_month,last_month'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        [$start, $end] = $this->resolveRange($validated);

        if (! $start || ! $end) {
            return Response::error('Podaj `period` albo parę `start_date` i `end_date` w formacie YYYY-MM-DD.');
        }

        $bundle = app(TaskPromptBundleService::class)->build($start, $end);

        $max = config('ai_tools.max_tasks_per_bundle');
        $count = $bundle['counts']['tasks'] ?? 0;

        if ($count > $max) {
            return Response::error(
                "Okres obejmuje {$count} zadań, limit to {$max}. Zawęź zakres dat i spróbuj ponownie."
            );
        }

        return Response::json($bundle);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function resolveRange(array $input): array
    {
        $period = $input['period'] ?? null;

        if ($period) {
            $now = Carbon::now();

            return match ($period) {
                'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                'last_week' => [
                    $now->copy()->subWeek()->startOfWeek(),
                    $now->copy()->subWeek()->endOfWeek(),
                ],
                'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
                'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                'last_month' => [
                    $now->copy()->subMonthNoOverflow()->startOfMonth(),
                    $now->copy()->subMonthNoOverflow()->endOfMonth(),
                ],
                default => [null, null],
            };
        }

        if (! empty($input['start_date']) && ! empty($input['end_date'])) {
            return [
                Carbon::parse($input['start_date']),
                Carbon::parse($input['end_date']),
            ];
        }

        return [null, null];
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'period' => $schema->string()
                ->description('Skrót zakresu dat. Pomija start_date i end_date, jeśli podany.')
                ->enum(['this_week', 'last_week', 'last_7_days', 'this_month', 'last_month']),
            'start_date' => $schema->string()
                ->description('Początek zakresu w formacie YYYY-MM-DD. Wymaga end_date.'),
            'end_date' => $schema->string()
                ->description('Koniec zakresu w formacie YYYY-MM-DD (włącznie). Wymaga start_date.'),
        ];
    }
}
