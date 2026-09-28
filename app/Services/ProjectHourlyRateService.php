<?php

namespace App\Services;

use App\Enums\ProjectType;
use App\Models\Project;
use App\Models\ProjectHourlyRate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectHourlyRateService
{
    /**
     * Pierwsza stawka projektu: kwota z kolumny, od startu projektu, bez końca.
     * Nic nie robi, gdy okres już istnieje — migracja i ponowne zapisy nie dublują historii.
     */
    public function seedOpeningRate(Project $project): ?ProjectHourlyRate
    {
        if ($project->type !== ProjectType::HOURLY || $project->hourly_rate === null) {
            return null;
        }

        if ($project->hourlyRates()->exists()) {
            return null;
        }

        $start = $project->start_date?->toDateString()
            ?? $project->created_at?->toDateString()
            ?? now()->toDateString();

        return $project->hourlyRates()->create([
            'amount' => $project->hourly_rate,
            'currency' => $project->currency ?: 'EUR',
            'start_date' => $start,
            'end_date' => null,
        ]);
    }

    /**
     * Nowa stawka od dnia. Poprzednia otwarta kończy się dzień wcześniej.
     * Kwota starych okresów zostaje. Kolumna hourly_rate trzyma już tylko bieżącą stawkę.
     */
    public function addRateFrom(Project $project, CarbonInterface $from, float $amount): ProjectHourlyRate
    {
        $from = Carbon::parse($from)->startOfDay();

        return DB::transaction(function () use ($project, $from, $amount) {
            $open = $project->hourlyRates()
                ->whereNull('end_date')
                ->orderByDesc('start_date')
                ->lockForUpdate()
                ->first();

            if ($open && $open->start_date->copy()->startOfDay()->gte($from)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Nowa stawka musi zaczynać się po '.$open->start_date->format('d.m.Y').'. Wcześniejszy dzień nadpisałby już obowiązującą stawkę.',
                ]);
            }

            $closedOverlap = $project->hourlyRates()
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $from->toDateString())
                ->exists();

            if ($closedOverlap) {
                throw ValidationException::withMessages([
                    'start_date' => 'Ten dzień wpada w już zamknięty okres stawki.',
                ]);
            }

            if ($open) {
                $open->end_date = $from->copy()->subDay()->toDateString();
                $open->save();
            }

            $rate = $project->hourlyRates()->create([
                'amount' => $amount,
                'currency' => $project->currency ?: ($open?->currency ?? 'EUR'),
                'start_date' => $from->toDateString(),
                'end_date' => null,
            ]);

            $project->hourly_rate = $amount;
            $project->save();

            return $rate;
        });
    }
}
