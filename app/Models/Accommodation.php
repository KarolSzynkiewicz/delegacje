<?php

namespace App\Models;

use App\Traits\HasComments;
use App\Traits\HasEquipmentConsumptions;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Accommodation extends Model
{
    use HasComments, HasEquipmentConsumptions, HasFactory;

    protected $fillable = [
        'location_id',
        'name',
        'address',
        'city',
        'postal_code',
        'country',
        'capacity',
        'description',
        'image_path',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'country' => \App\Enums\EuropeanCountry::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    // ── Relacje ──────────────────────────────────────────────────────────────

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(AccommodationLease::class)->orderByDesc('start_date');
    }

    /**
     * Aktywny najem: taki, gdzie end_date jest null lub w przyszłości, wybrany najnowszy start_date.
     */
    public function activeLease(): HasOne
    {
        return $this->hasOne(AccommodationLease::class)->ofMany(
            ['start_date' => 'max'],
            fn ($q) => $q->where(
                fn ($inner) => $inner->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())
            )
        );
    }

    /**
     * Własne: nigdy nie miały umowy najmu.
     */
    public function scopeOwned(Builder $query): Builder
    {
        return $query->whereDoesntHave('leases', fn (Builder $q) => $q->where('type', 'wynajmowany'));
    }

    /**
     * Wynajmowane w danym dniu (domyślnie dziś).
     */
    public function scopeActivelyRented(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $day = Carbon::parse($date ?? now())->toDateString();

        return $query->whereHas('leases', fn (Builder $q) => $q
            ->where('type', 'wynajmowany')
            ->coveringDate($day));
    }

    /**
     * Były wynajmowane, ale w danym dniu nie ma już umowy.
     */
    public function scopeEndedRental(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $day = Carbon::parse($date ?? now())->toDateString();

        return $query
            ->whereDoesntHave('leases', fn (Builder $q) => $q->coveringDate($day))
            ->whereHas('leases', fn (Builder $q) => $q
                ->where('type', 'wynajmowany')
                ->where(fn (Builder $inner) => $inner->whereNull('start_date')->orWhere('start_date', '<=', $day)));
    }

    /**
     * Portfolio na dany dzień: własne albo z najmem pokrywającym ten dzień.
     */
    public function scopeCurrentlyHeld(Builder $query, CarbonInterface|string|null $date = null): Builder
    {
        $day = Carbon::parse($date ?? now())->toDateString();

        return $query->where(function (Builder $q) use ($day) {
            $q->whereDoesntHave('leases', fn (Builder $lease) => $lease->where('type', 'wynajmowany'))
                ->orWhereHas('leases', fn (Builder $lease) => $lease
                    ->where('type', 'wynajmowany')
                    ->coveringDate($day));
        });
    }

    /**
     * owned | rented | ended — na wskazany dzień, niezależnie od wirtualnego type.
     */
    public function currentTenure(CarbonInterface|string|null $date = null): string
    {
        $day = Carbon::parse($date ?? now());
        $lease = $this->leaseCoveringDate($day);

        if ($lease) {
            return $lease->type === 'wynajmowany' ? 'rented' : 'owned';
        }

        $hadRentalByThen = $this->relationLoaded('leases')
            ? $this->leases->contains(function (AccommodationLease $lease) use ($day) {
                if ($lease->type !== 'wynajmowany') {
                    return false;
                }
                $start = $lease->start_date?->toDateString();

                return ! $start || $start <= $day->toDateString();
            })
            : $this->leases()
                ->where('type', 'wynajmowany')
                ->where(fn (Builder $q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $day->toDateString()))
                ->exists();

        return $hadRentalByThen ? 'ended' : 'owned';
    }

    // ── Wirtualne akcesory (kompatybilność wsteczna) ─────────────────────────

    public function getTypeAttribute(): string
    {
        return $this->activeLease?->type ?? 'własny';
    }

    public function getLeaseStartDateAttribute(): ?\Carbon\Carbon
    {
        return $this->activeLease?->start_date;
    }

    public function getLeaseEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->activeLease?->end_date;
    }

    public function getIsRentedAttribute(): bool
    {
        return $this->getTypeAttribute() === 'wynajmowany';
    }

    /**
     * Get all assignments for this accommodation.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AccommodationAssignment::class);
    }

    /**
     * Get the employees assigned to this accommodation (M:N relationship).
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'accommodation_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    /**
     * Get current active assignments for this accommodation.
     */
    public function currentAssignments()
    {
        return $this->assignments()->active()->get();
    }

    /**
     * Najem, który w całości pokrywa podany zakres dat (jeden rekord).
     */
    public function leaseCoveringRange($startDate, $endDate): ?AccommodationLease
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        if ($this->relationLoaded('leases')) {
            return $this->leases
                ->first(function (AccommodationLease $lease) use ($start, $end) {
                    $leaseStart = $lease->start_date?->toDateString();
                    $leaseEnd = $lease->end_date?->toDateString();

                    if ($leaseStart && $leaseStart > $start) {
                        return false;
                    }

                    return ! ($leaseEnd && $leaseEnd < $end);
                });
        }

        return $this->leases()
            ->where(function ($q) use ($start) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $start);
            })
            ->where(function ($q) use ($end) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $end);
            })
            ->orderByDesc('start_date')
            ->first();
    }

    /**
     * Najem pokrywający konkretny dzień.
     */
    public function leaseCoveringDate($date): ?AccommodationLease
    {
        return $this->leaseCoveringRange($date, $date);
    }

    /**
     * Szczytowe obłożenie w zakresie: ile osób śpi tu jednocześnie
     * w najgorszym dniu, a nie ile rekordów nakłada się na cały zakres.
     *
     * (A: 1–10, B: 11–20 przy pojemności 2 zostawia wolne miejsce na 1–20;
     *  stare count() traktowało to jako 2 zajęte i blokowało zapis.)
     */
    public function getPeakOccupancy($startDate, $endDate, ?int $excludeAssignmentId = null): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lt($start)) {
            return 0;
        }

        $query = $this->assignments()->inDateRange($start, $end);
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $assignments = $query->get(['id', 'start_date', 'end_date']);
        if ($assignments->isEmpty()) {
            return 0;
        }

        $events = [];
        foreach ($assignments as $assignment) {
            $aStart = Carbon::parse($assignment->start_date)->startOfDay();
            $aEnd = $assignment->end_date
                ? Carbon::parse($assignment->end_date)->startOfDay()
                : $end->copy();

            $overlapStart = $aStart->greaterThan($start) ? $aStart : $start->copy();
            $overlapEnd = $aEnd->lessThan($end) ? $aEnd : $end->copy();
            if ($overlapStart->greaterThan($overlapEnd)) {
                continue;
            }

            $events[] = [$overlapStart->getTimestamp(), 1];
            $events[] = [$overlapEnd->copy()->addDay()->startOfDay()->getTimestamp(), -1];
        }

        if ($events === []) {
            return 0;
        }

        usort($events, function (array $a, array $b): int {
            if ($a[0] === $b[0]) {
                return $a[1] <=> $b[1];
            }

            return $a[0] <=> $b[0];
        });

        $current = 0;
        $peak = 0;
        foreach ($events as [, $delta]) {
            $current += $delta;
            if ($current > $peak) {
                $peak = $current;
            }
        }

        return $peak;
    }

    /**
     * Pierwszy dzień w zakresie, w którym obłożenie >= pojemność (brak wolnego łóżka).
     */
    public function firstDateAtCapacity($startDate, $endDate, ?int $excludeAssignmentId = null): ?Carbon
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $capacity = (int) $this->capacity;

        $day = $start->copy();
        while ($day->lte($end)) {
            if ($this->getPeakOccupancy($day, $day, $excludeAssignmentId) >= $capacity) {
                return $day->copy();
            }
            $day->addDay();
        }

        return null;
    }

    /**
     * Wolne miejsca w zakresie = pojemność minus szczytowe obłożenie.
     */
    public function getAvailableCapacity($startDate, $endDate, ?int $excludeAssignmentId = null): int
    {
        return max(0, (int) $this->capacity - $this->getPeakOccupancy($startDate, $endDate, $excludeAssignmentId));
    }

    /**
     * Check if accommodation has available space in a given date range.
     */
    public function hasAvailableSpace($startDate, $endDate, ?int $excludeAssignmentId = null): bool
    {
        return $this->getAvailableCapacity($startDate, $endDate, $excludeAssignmentId) > 0;
    }

    /**
     * Get full address string for geocoding.
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code,
            $this->country?->value ?? null,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if accommodation has coordinates.
     */
    public function hasCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    /**
     * Liczba "osobonocy" (person-nights) w danym okresie — suma dni nakładania się
     * przypisań pracowników (AccommodationAssignment) na ten okres. Używana do liczenia
     * kosztu najmu przypadającego na jedną osobę/noc (kontroling + eksport JSON dla LLM).
     * Wspólna metoda dla ProfitabilityService i CostPromptBundleService.
     */
    public function occupancyNightsBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        $assignments = $this->relationLoaded('assignments')
            ? $this->assignments->filter(function ($assignment) use ($start, $end) {
                $aStart = $assignment->start_date ? Carbon::parse($assignment->start_date)->toDateString() : null;
                $aEnd = $assignment->end_date ? Carbon::parse($assignment->end_date)->toDateString() : null;
                if ($aStart && $aStart > $end->toDateString()) {
                    return false;
                }
                if ($aEnd && $aEnd < $start->toDateString()) {
                    return false;
                }

                return true;
            })
            : $this->assignments()
                ->where('start_date', '<=', $end->toDateString())
                ->where(function ($q) use ($start) {
                    $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $start->toDateString());
                })
                ->get();

        $totalNights = 0;
        foreach ($assignments as $assignment) {
            $aStart = $assignment->start_date ? Carbon::parse($assignment->start_date) : $start;
            $aEnd = $assignment->end_date ? Carbon::parse($assignment->end_date) : $end;

            $overlapStart = $aStart->gt($start) ? $aStart : $start;
            $overlapEnd = $aEnd->lt($end) ? $aEnd : $end;

            if ($overlapStart->gt($overlapEnd)) {
                continue;
            }

            $totalNights += (int) $overlapStart->copy()->startOfDay()->diffInDays($overlapEnd->copy()->endOfDay()) + 1;
        }

        return $totalNights;
    }

    /**
     * Get coordinates as array [lat, lng].
     */
    public function getCoordinates(): ?array
    {
        if (! $this->hasCoordinates()) {
            return null;
        }

        return [
            (float) $this->latitude,
            (float) $this->longitude,
        ];
    }
}
