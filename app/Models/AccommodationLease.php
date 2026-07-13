<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccommodationLease extends Model
{
    protected $fillable = [
        'accommodation_id',
        'type',
        'start_date',
        'end_date',
        'monthly_rent',
        'currency',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_rent' => 'decimal:2',
    ];

    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    public function isActive(): bool
    {
        return is_null($this->end_date) || $this->end_date->gte(now()->startOfDay());
    }

    public function getPeriodLabelAttribute(): string
    {
        $from = $this->start_date?->format('d.m.Y') ?? '—';
        $to = $this->end_date?->format('d.m.Y') ?? 'bezterminowo';

        return "{$from} – {$to}";
    }

    /**
     * Koszt najmu przypadający na dany okres (np. miesiąc raportowy) — jedyne, wspólne
     * miejsce liczenia tej metodologii, używane zarówno przez kontroling rentowności,
     * jak i przez eksport JSON dla LLM (CostPromptBundleService), żeby nigdzie nie
     * rozjeżdżały się dwie różne wersje tej samej kalkulacji.
     *
     * Logika hybrydowa w zależności od tego, czy najem ma określony koniec:
     * - Umowa na czas określony (`end_date` ustawiona): `monthly_rent` traktujemy jako
     *   CAŁKOWITĄ kwotę za cały podpisany okres umowy (np. umowa na 2 miesiące za 200€
     *   → 100€ na każdy z tych 2 miesięcy). Koszt = kwota × (dni_nakładania / dni_całej_umowy).
     * - Umowa bezterminowa (`end_date` = null): `monthly_rent` to nawracająca stawka
     *   miesięczna — koszt = kwota / 30 × dni_nakładania (uproszczenie księgowe: pełny
     *   miesiąc kalendarzowy = 1 pełny czynsz).
     */
    public function amountForPeriod(CarbonInterface $periodStart, CarbonInterface $periodEnd): float
    {
        if ($this->monthly_rent === null) {
            return 0.0;
        }

        $leaseStart = $this->start_date ? Carbon::parse($this->start_date) : $periodStart;
        $leaseEnd = $this->end_date ? Carbon::parse($this->end_date) : null;

        $overlapStart = $leaseStart->gt($periodStart) ? $leaseStart : $periodStart;
        $overlapEnd = ($leaseEnd && $leaseEnd->lt($periodEnd)) ? $leaseEnd : $periodEnd;

        if ($overlapStart->gt($overlapEnd)) {
            return 0.0;
        }

        $overlapDays = self::inclusiveDays($overlapStart, $overlapEnd);
        $amount = (float) $this->monthly_rent;

        if ($leaseEnd !== null) {
            $totalDays = self::inclusiveDays($leaseStart, $leaseEnd);
            if ($totalDays <= 0) {
                return 0.0;
            }

            return round($amount * $overlapDays / $totalDays, 2);
        }

        return round($amount * $overlapDays / 30, 2);
    }

    protected static function inclusiveDays(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1;
    }
}
