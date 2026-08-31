<?php

namespace App\Mcp\Concerns;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;

trait ResolvesPeriodRange
{
    /**
     * @param  array<string, mixed>  $input
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function resolveRange(array $input): array
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
                Carbon::parse($input['start_date'])->startOfDay(),
                Carbon::parse($input['end_date'])->endOfDay(),
            ];
        }

        return [null, null];
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    protected function periodSchema(JsonSchema $schema): array
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
