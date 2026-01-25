<?php

namespace App\Services;

use App\Models\FixedCostTemplate;
use App\Models\FixedCostEntry;
use Carbon\Carbon;

class GenerateFixedCostsService
{
    /**
     * Generuj koszty stałe dla wszystkich aktywnych szablonów w danym okresie
     */
    public function generateForPeriod(Carbon $periodStart, Carbon $periodEnd, ?string $notes = null): array
    {
        $generated = 0;
        $skipped = 0;
        $errors = [];

        // Pobierz wszystkie aktywne szablony
        $templates = FixedCostTemplate::where('is_active', true)->get();

        foreach ($templates as $template) {
            try {
                // Sprawdź czy szablon jest aktywny dla tego okresu
                if (!$template->isActiveForPeriod($periodStart, $periodEnd)) {
                    $skipped++;
                    continue;
                }

                // Sprawdź czy entry już istnieje dla tego okresu
                if (FixedCostEntry::existsForPeriodAndTemplate($template->id, $periodStart, $periodEnd)) {
                    $skipped++;
                    continue;
                }

                // Określ datę księgowania na podstawie interwału
                $accountingDate = $this->determineAccountingDate($template, $periodStart, $periodEnd);

                // Utwórz entry
                FixedCostEntry::create([
                    'name' => $template->name,
                    'amount' => $template->amount,
                    'currency' => $template->currency,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'accounting_date' => $accountingDate->toDateString(),
                    'template_id' => $template->id,
                    'notes' => $notes ?? $template->notes,
                ]);

                $generated++;
            } catch (\Exception $e) {
                $errors[] = "Szablon '{$template->name}': " . $e->getMessage();
            }
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Określ datę księgowania na podstawie typu interwału
     */
    protected function determineAccountingDate(
        FixedCostTemplate $template,
        Carbon $periodStart,
        Carbon $periodEnd
    ): Carbon {
        switch ($template->interval_type) {
            case 'monthly':
                // Dla miesięcznego: accounting_date = pierwszy dzień okresu z ustawionym dniem miesiąca
                $date = $periodStart->copy();
                $day = min($template->interval_day, $date->daysInMonth);
                return $date->day($day);
            
            case 'weekly':
                // Dla tygodniowego: accounting_date = pierwszy dzień tygodnia okresu
                return $periodStart->copy()->startOfWeek();
            
            case 'yearly':
                // Dla rocznego: accounting_date = pierwszy dzień roku z ustawionym dniem/miesiącem
                $date = $periodStart->copy()->startOfYear();
                // interval_day może być w formacie MMDD (np. 0101 = 1 stycznia)
                // lub po prostu dzień roku (1-365)
                if ($template->interval_day <= 31) {
                    // Traktuj jako dzień miesiąca pierwszego miesiąca
                    return $date->day($template->interval_day);
                }
                return $date->addDays($template->interval_day - 1);
            
            default:
                return $periodStart->copy();
        }
    }
}
