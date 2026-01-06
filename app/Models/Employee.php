<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
        'image_path'
    ];

    /**
     * Get all roles associated with the employee.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'employee_role')->withTimestamps();
    }

    /**
     * Get all project assignments for this employee.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Get the projects assigned to this employee (M:N relationship).
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_assignments')
            ->withPivot('role_id', 'start_date', 'end_date', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get all vehicle assignments for this employee.
     */
    public function vehicleAssignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    /**
     * Get the vehicles assigned to this employee (M:N relationship).
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    /**
     * Get all accommodation assignments for this employee.
     */
    public function accommodationAssignments(): HasMany
    {
        return $this->hasMany(AccommodationAssignment::class);
    }

    /**
     * Get the accommodations assigned to this employee (M:N relationship).
     */
    public function accommodations(): BelongsToMany
    {
        return $this->belongsToMany(Accommodation::class, 'accommodation_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }

    /**
     * Get active project assignments for this employee.
     */
    public function activeAssignments(): HasMany
    {
        return $this->assignments()->where('status', 'active');
    }

    /**
     * Get active vehicle assignment for this employee.
     */
    public function activeVehicleAssignment()
    {
        return $this->vehicleAssignments()->active()->first();
    }

    /**
     * Get active accommodation assignment for this employee.
     */
    public function activeAccommodationAssignment()
    {
        return $this->accommodationAssignments()->active()->first();
    }

    /**
     * Get the full name of the employee.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the image URL for the employee.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * Get all documents for this employee.
     */
    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Get all rotations for this employee.
     */
    public function rotations(): HasMany
    {
        return $this->hasMany(Rotation::class);
    }

    /**
     * Get active rotations for this employee (based on dates, not database status).
     */
    public function activeRotations(): HasMany
    {
        $today = now()->toDateString();
        return $this->rotations()
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            });
    }

    /**
     * Check if employee has a rotation (active or scheduled) that covers the entire date range.
     * Rotacja musi pokrywać CAŁY okres przypisania (od start_date do end_date).
     * Sprawdza zarówno rotacje aktywne jak i zaplanowane (nie anulowane).
     */
    public function hasActiveRotationInDateRange($startDate, $endDate): bool
    {
        // Sprawdź czy istnieje rotacja (aktywna lub zaplanowana, nie anulowana),
        // która pokrywa CAŁY okres przypisania
        // Rotacja musi zaczynać się przed lub w dniu rozpoczęcia przypisania
        // i kończyć się po lub w dniu zakończenia przypisania
        $hasCoveringRotation = $this->rotations()
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            })
            ->where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $endDate)
            ->exists();

        if ($hasCoveringRotation) {
            return true;
        }

        // Jeśli nie ma jednej rotacji pokrywającej cały okres,
        // sprawdź czy istnieje ciąg rotacji pokrywających cały okres
        return $this->hasContinuousRotationsCoveringRange($startDate, $endDate);
    }

    /**
     * Check if employee has continuous rotations covering the entire date range.
     * Sprawdza czy istnieje ciąg rotacji (bez przerw), które razem pokrywają cały okres.
     * Sprawdza zarówno rotacje aktywne jak i zaplanowane (nie anulowane).
     */
    protected function hasContinuousRotationsCoveringRange($startDate, $endDate): bool
    {
        // Pobierz wszystkie rotacje (aktywne lub zaplanowane, nie anulowane), które nakładają się z okresem
        $rotations = $this->rotations()
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            })
            ->where(function ($q) use ($startDate, $endDate) {
                // Rotacja zaczyna się przed końcem okresu i kończy po początku okresu
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->orderBy('start_date')
            ->get();

        if ($rotations->isEmpty()) {
            return false;
        }

        // Sprawdź czy rotacje pokrywają cały okres bez przerw
        $targetStart = \Carbon\Carbon::parse($startDate)->startOfDay();
        $targetEnd = \Carbon\Carbon::parse($endDate)->startOfDay();
        $coveredUntil = $targetStart->copy();

        foreach ($rotations as $rotation) {
            $rotationStart = \Carbon\Carbon::parse($rotation->start_date)->startOfDay();
            $rotationEnd = \Carbon\Carbon::parse($rotation->end_date)->startOfDay();

            // Jeśli rotacja zaczyna się przed lub w dniu pokrytego okresu
            if ($rotationStart->lte($coveredUntil)) {
                // Jeśli rotacja kończy się później niż dotychczas pokryty okres, rozszerz pokrycie
                if ($rotationEnd->gte($coveredUntil)) {
                    $coveredUntil = $rotationEnd->copy()->addDay();
                }
            } else {
                // Jest przerwa - rotacja nie pokrywa ciągłości
                return false;
            }

            // Jeśli pokryliśmy cały okres, zwróć true
            if ($coveredUntil->gt($targetEnd)) {
                return true;
            }
        }

        // Sprawdź czy pokryliśmy cały okres
        return $coveredUntil->gt($targetEnd);
    }

    /**
     * Get the active rotation that covers a given date.
     */
    public function getActiveRotationForDate($date): ?Rotation
    {
        return $this->activeRotations()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * Check if employee has all required documents active in date range.
     */
    public function hasAllDocumentsActiveInDateRange($startDate, $endDate): bool
    {
        // Pobierz wszystkie typy dokumentów
        $allDocumentTypes = \App\Models\Document::pluck('id');
        
        if ($allDocumentTypes->isEmpty()) {
            return true; // Jeśli nie ma żadnych typów dokumentów, uznajemy że dokumenty są OK
        }

        // Dla każdego typu dokumentu sprawdź czy pracownik ma aktywny dokument w okresie
        foreach ($allDocumentTypes as $documentTypeId) {
            $hasActiveDocument = $this->employeeDocuments()
                ->where('document_id', $documentTypeId)
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where(function ($q2) use ($startDate, $endDate) {
                        // Dokument bezokresowy - zawsze aktywny jeśli valid_from <= endDate
                        $q2->where('kind', 'bezokresowy')
                           ->where('valid_from', '<=', $endDate);
                    })->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Dokument okresowy - musi być aktywny w całym zakresie
                        $q2->where('kind', 'okresowy')
                           ->where('valid_from', '<=', $startDate)
                           ->where(function ($q3) use ($endDate) {
                               $q3->whereNull('valid_to')
                                  ->orWhere('valid_to', '>=', $endDate);
                           });
                    });
                })
                ->exists();

            if (!$hasActiveDocument) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get availability status with reasons for a given date range.
     * Returns array with 'available' boolean and 'reasons' array.
     */
    public function getAvailabilityStatus($startDate, $endDate): array
    {
        $reasons = [];
        $available = true;

        // 1. Sprawdź dokumenty
        if (!$this->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
            $available = false;
            $reasons[] = 'Brak wszystkich wymaganych dokumentów aktywnych w tym okresie';
        }

        // 2. Sprawdź rotację
        if (!$this->hasActiveRotationInDateRange($startDate, $endDate)) {
            $available = false;
            $reasons[] = 'Brak rotacji pokrywającej cały okres';
        }

        // 3. Sprawdź konfliktujące przypisania
        $hasConflictingAssignments = $this->assignments()
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        if ($hasConflictingAssignments) {
            $available = false;
            $reasons[] = 'Już przypisany do innego projektu w tym okresie';
        }

        return [
            'available' => $available,
            'reasons' => $reasons,
        ];
    }

    /**
     * Check if employee is available in a given date range.
     * Updated: Now checks both rotations AND assignments.
     */
    public function isAvailableInDateRange($startDate, $endDate): bool
    {
        // 1. Sprawdź czy pracownik ma wszystkie wymagane dokumenty aktywne
        if (!$this->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
            return false;
        }

        // 2. Sprawdź czy pracownik ma aktywną rotację w tym okresie
        if (!$this->hasActiveRotationInDateRange($startDate, $endDate)) {
            return false; // Brak rotacji = pracownik nie może pracować
        }

        // 3. Sprawdź czy nie ma konfliktujących przypisań
        $hasConflictingAssignments = $this->assignments()
            ->where('status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->exists();

        return !$hasConflictingAssignments;
    }
}
