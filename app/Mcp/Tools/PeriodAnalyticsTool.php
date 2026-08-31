<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Mcp\Concerns\ResolvesPeriodRange;
use App\Services\PeriodTaskAnalyticsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class PeriodAnalyticsTool extends Tool
{
    use ActsAsConfiguredUser;
    use ResolvesPeriodRange;

    protected string $name = 'period_analytics';

    protected string $description = <<<'MARKDOWN'
        Zwraca zwarty raport KPI i współpracy za okres: statusy, load per osoba,
        kategorie, czas życia, najgorętsze wątki (same ID), zadania bez aktywności,
        macierze komentarzy / delegacji / @wzmianek oraz kto domyka cudze podzadania.

        Nie zawiera ciał komentarzy ani opisów – do treści użyj `get_task`
        i `get_task_comments` na ID z `pointers` / `hottest_threads` / `stale`.

        Użyj `period` albo pary `start_date` i `end_date`.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $this->actingUser();

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:this_week,last_week,last_7_days,this_month,last_month'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'stale_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        [$start, $end] = $this->resolveRange($validated);

        if (! $start || ! $end) {
            return Response::error('Podaj `period` albo parę `start_date` i `end_date` w formacie YYYY-MM-DD.');
        }

        $staleDays = (int) ($validated['stale_days'] ?? 7);

        return Response::json(
            app(PeriodTaskAnalyticsService::class)->build($start, $end, $staleDays)
        );
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->periodSchema($schema),
            'stale_days' => $schema->integer()
                ->description('Próg bezczynności w dniach dla listy stale. Domyślnie 7.')
                ->min(1)
                ->max(90),
        ];
    }
}
