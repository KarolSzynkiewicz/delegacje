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
     * Check if employee has a specific role.
     */
    public function hasRole(int $roleId): bool
    {
        return $this->roles()->where('roles.id', $roleId)->exists();
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
        return $this->assignments()->active();
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
     * Get the current location of this employee.
     * 
     * Delegates to LocationTrackingService for business logic.
     * 
     * @return \App\Models\Location|null
     */
    public function getCurrentLocation(): ?Location
    {
        return app(\App\Services\LocationTrackingService::class)->forEmployee($this);
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
        return $this->rotations()->active();
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
        $rotations = $this->rotations()
            ->overlappingWith($startDate, $endDate)
            ->get();

        if ($rotations->isEmpty()) {
            return false;
        }

        // Sprawdź czy któraś rotacja pokrywa cały okres
        foreach ($rotations as $rotation) {
            if ($rotation->covers($startDate, $endDate)) {
                return true;
            }
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
            ->overlappingWith($startDate, $endDate)
            ->orderBy('start_date')
            ->get();

        if ($rotations->isEmpty()) {
            return false;
        }

        // Sprawdź czy rotacje pokrywają cały okres bez przerw
        $targetStart = \App\Services\DateRangeService::normalizeDate($startDate);
        $targetEnd = \App\Services\DateRangeService::normalizeDate($endDate);
        $coveredUntil = $targetStart->copy();

        foreach ($rotations as $rotation) {
            $rotationStart = $rotation->getStartDate();
            $rotationEnd = $rotation->getEndDate();
            
            if ($rotationStart === null || $rotationEnd === null) {
                continue; // Skip rotations without dates
            }
            
            $rotationStart = \App\Services\DateRangeService::normalizeDate($rotationStart);
            $rotationEnd = \App\Services\DateRangeService::normalizeDate($rotationEnd);

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
        return $this->rotations()
            ->activeAtDate($date)
            ->first();
    }

    /**
     * Check if employee has all required documents active in date range.
     */
    public function hasAllDocumentsActiveInDateRange($startDate, $endDate): bool
    {
        // Sprawdź czy kolumna is_required istnieje w tabeli documents
        $hasIsRequiredColumn = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'is_required');
        
        // Jeśli kolumna nie istnieje, nie ma wymaganych dokumentów - wszystko OK
        if (!$hasIsRequiredColumn) {
            return true;
        }
        
        // Pobierz tylko wymagane dokumenty (is_required = true)
        $requiredDocuments = \App\Models\Document::where('is_required', true)->pluck('id');
        
        // Jeśli nie ma żadnych wymaganych dokumentów, uznajemy że dokumenty są OK
        if ($requiredDocuments->isEmpty()) {
            return true;
        }

        // Dla każdego wymaganego dokumentu sprawdź czy pracownik ma aktywny dokument w okresie
        foreach ($requiredDocuments as $documentTypeId) {
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
    public function getAvailabilityStatus($startDate, $endDate, ?int $excludeAssignmentId = null): array
    {
        $reasons = [];
        $available = true;
        $missingDocuments = [];

        // 1. Sprawdź dokumenty - szczegółowo
        // Upewnij się, że relacja jest załadowana
        if (!$this->relationLoaded('employeeDocuments')) {
            $this->load('employeeDocuments.document');
        }
        
        // Sprawdź czy kolumna is_required istnieje
        $hasIsRequiredColumn = \Illuminate\Support\Facades\Schema::hasColumn('documents', 'is_required');
        
        // Sprawdź tylko wymagane dokumenty lub wszystkie jeśli kolumna nie istnieje
        if ($hasIsRequiredColumn) {
            $requiredDocuments = \App\Models\Document::where('is_required', true)->get();
        } else {
            $requiredDocuments = \App\Models\Document::all();
        }
        
        // Jeśli nie ma żadnych wymaganych dokumentów, nie sprawdzaj nic
        if ($hasIsRequiredColumn && $requiredDocuments->isEmpty()) {
            // Nie ma wymaganych dokumentów - wszystko OK
        } elseif (!$this->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
            $available = false;
            
            foreach ($requiredDocuments as $document) {
                $employeeDoc = $this->employeeDocuments->where('document_id', $document->id)->first();
                
                if (!$employeeDoc) {
                    $missingDocuments[] = [
                        'document_id' => $document->id,
                        'document_name' => $document->name,
                        'problem' => 'Brak dokumentu',
                        'kind' => null,
                        'valid_from' => null,
                        'valid_to' => null,
                        'is_required' => $hasIsRequiredColumn ? $document->is_required : true,
                    ];
                    continue;
                }
                
                // Sprawdź czy dokument jest aktywny w całym zakresie
                $isValid = false;
                $problem = '';
                
                if ($employeeDoc->kind === 'bezokresowy') {
                    if ($employeeDoc->valid_from > $endDate) {
                        $isValid = false;
                        $problem = "Dokument zaczyna się za późno ({$employeeDoc->valid_from->format('Y-m-d')} > {$endDate})";
                    } else {
                        $isValid = true;
                    }
                } else {
                    // Dokument okresowy
                    if ($employeeDoc->valid_from > $startDate) {
                        $isValid = false;
                        $problem = "Dokument zaczyna się za późno ({$employeeDoc->valid_from->format('Y-m-d')} > {$startDate})";
                    } elseif ($employeeDoc->valid_to && $employeeDoc->valid_to < $endDate) {
                        $isValid = false;
                        $problem = "Dokument kończy się za wcześnie ({$employeeDoc->valid_to->format('Y-m-d')} < {$endDate})";
                    } else {
                        $isValid = true;
                    }
                }
                
                if (!$isValid) {
                    $missingDocuments[] = [
                        'document_id' => $document->id,
                        'document_name' => $document->name,
                        'problem' => $problem,
                        'kind' => $employeeDoc->kind,
                        'valid_from' => $employeeDoc->valid_from->format('Y-m-d'),
                        'valid_to' => $employeeDoc->valid_to ? $employeeDoc->valid_to->format('Y-m-d') : null,
                        'employee_document_id' => $employeeDoc->id,
                        'is_required' => $hasIsRequiredColumn ? $document->is_required : true,
                    ];
                }
            }
            
            // Filtruj tylko wymagane dokumenty jeśli kolumna istnieje
            if ($hasIsRequiredColumn) {
                $missingDocuments = array_filter($missingDocuments, function($doc) {
                    return isset($doc['is_required']) && $doc['is_required'] === true;
                });
            }
            
            if (!empty($missingDocuments)) {
                // Sprawdź czy kolumna is_required istnieje, aby dostosować komunikat
                if ($hasIsRequiredColumn) {
                    $reasons[] = 'Brak wszystkich wymaganych dokumentów aktywnych w tym okresie';
                } else {
                    $reasons[] = 'Brak wszystkich dokumentów aktywnych w tym okresie';
                }
            }
        }

        // 2. Sprawdź rotację
        if (!$this->hasActiveRotationInDateRange($startDate, $endDate)) {
            $available = false;
            $reasons[] = 'Brak rotacji pokrywającej cały okres';
        }

        // 3. Sprawdź konfliktujące przypisania (wykluczając aktualnie edytowane przypisanie)
        $query = $this->assignments()
            ->active()
            ->overlappingWith($startDate, $endDate);
        
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        $hasConflictingAssignments = $query->exists();

        if ($hasConflictingAssignments) {
            $available = false;
            $reasons[] = 'Już przypisany do innego projektu w tym okresie';
        }

        return [
            'available' => $available,
            'reasons' => $reasons,
            'missing_documents' => $missingDocuments,
        ];
    }

    /**
     * Check if employee is available in a given date range.
     * Updated: Now checks both rotations AND assignments.
     */
    public function isAvailableInDateRange($startDate, $endDate, ?int $excludeAssignmentId = null): bool
    {
        // 1. Sprawdź czy pracownik ma wszystkie wymagane dokumenty aktywne
        if (!$this->hasAllDocumentsActiveInDateRange($startDate, $endDate)) {
            return false;
        }

        // 2. Sprawdź czy pracownik ma aktywną rotację w tym okresie
        if (!$this->hasActiveRotationInDateRange($startDate, $endDate)) {
            return false; // Brak rotacji = pracownik nie może pracować
        }

        // 3. Sprawdź czy nie ma konfliktujących przypisań (wykluczając aktualnie edytowane przypisanie)
        $query = $this->assignments()
            ->active()
            ->overlappingWith($startDate, $endDate);
        
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        $hasConflictingAssignments = $query->exists();

        return !$hasConflictingAssignments;
    }

    /**
     * Get all adjustments for this employee.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class);
    }

    /**
     * Get all advances for this employee.
     */
    public function advances(): HasMany
    {
        return $this->hasMany(Advance::class);
    }

    /**
     * Get all payrolls for this employee.
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
