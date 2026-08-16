<?php

namespace App\Models;

use App\Traits\HasComments;
use App\Traits\HasEquipmentConsumptions;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
     * Get available capacity at a given date range.
     */
    public function getAvailableCapacity($startDate, $endDate, ?int $excludeAssignmentId = null): int
    {
        $query = $this->assignments()
            ->inDateRange($startDate, $endDate);

        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }

        $occupiedCount = $query->count();

        return max(0, $this->capacity - $occupiedCount);
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
        $assignments = $this->assignments()
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
