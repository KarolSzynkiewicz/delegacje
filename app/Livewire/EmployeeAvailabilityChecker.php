<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class EmployeeAvailabilityChecker extends Component
{
    public $employeeId;
    public $startDate;
    public $endDate;
    public $isAvailable = null;
    public $conflicts = [];
    public $availabilityStatus = null;
    public $missingDocuments = [];
    public $rotationDetails = null;

    public function mount($employeeId = null, $startDate = null, $endDate = null)
    {
        $this->employeeId = $employeeId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        if ($this->employeeId && $this->startDate) {
            $this->checkAvailability();
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['employeeId', 'startDate', 'endDate'])) {
            $this->checkAvailability();
        }
    }

    public function checkAvailability()
    {
        if (!$this->employeeId || !$this->startDate) {
            $this->isAvailable = null;
            $this->availabilityStatus = null;
            $this->missingDocuments = [];
            $this->rotationDetails = null;
            return;
        }

        $employee = Employee::with(['employeeDocuments.document', 'rotations'])->find($this->employeeId);
        if (!$employee) {
            $this->isAvailable = null;
            $this->availabilityStatus = null;
            $this->missingDocuments = [];
            $this->rotationDetails = null;
            return;
        }

        $endDate = $this->endDate ?: $this->startDate;
        
        // Pobierz pełny status dostępności z szczegółami
        $this->availabilityStatus = $employee->getAvailabilityStatus($this->startDate, $endDate);
        $this->isAvailable = $this->availabilityStatus['available'] ?? false;
        $this->missingDocuments = $this->availabilityStatus['missing_documents'] ?? [];
        
        // Sprawdź szczegóły rotacji
        $this->rotationDetails = $this->checkRotationDetails($employee, $this->startDate, $endDate);
        
        if (!$this->isAvailable) {
            $this->conflicts = $employee->assignments()
                ->active()
                ->overlappingWith($this->startDate, $endDate)
                ->with('project')
                ->get();
        } else {
            $this->conflicts = [];
        }
    }

    protected function checkRotationDetails(Employee $employee, string $startDate, string $endDate): ?array
    {
        // Pobierz wszystkie rotacje (nie anulowane), które nakładają się z okresem
        $rotations = $employee->rotations()
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('start_date', '<=', $endDate)
                  ->where('end_date', '>=', $startDate);
            })
            ->orderBy('start_date')
            ->get();

        if ($rotations->isEmpty()) {
            return [
                'has_rotation' => false,
                'covers_full_period' => false,
                'message' => 'Brak rotacji',
                'rotations' => []
            ];
        }

        // Sprawdź czy rotacje pokrywają cały okres
        $targetStart = \Carbon\Carbon::parse($startDate);
        $targetEnd = \Carbon\Carbon::parse($endDate);
        $coveredUntil = $targetStart->copy();
        $hasGaps = false;
        $firstGap = null;

        foreach ($rotations as $rotation) {
            $rotationStart = \Carbon\Carbon::parse($rotation->start_date);
            $rotationEnd = \Carbon\Carbon::parse($rotation->end_date);

            // Sprawdź czy jest przerwa
            if ($rotationStart->gt($coveredUntil)) {
                $hasGaps = true;
                if (!$firstGap) {
                    $firstGap = [
                        'from' => $coveredUntil->format('Y-m-d'),
                        'to' => $rotationStart->format('Y-m-d')
                    ];
                }
            }

            // Rozszerz pokrycie
            if ($rotationEnd->gt($coveredUntil)) {
                $coveredUntil = $rotationEnd->copy();
            }
        }

        $coversFullPeriod = !$hasGaps && $coveredUntil->gte($targetEnd);

        return [
            'has_rotation' => true,
            'covers_full_period' => $coversFullPeriod,
            'has_gaps' => $hasGaps,
            'first_gap' => $firstGap,
            'message' => $coversFullPeriod 
                ? 'Rotacja pokrywa cały okres' 
                : ($hasGaps 
                    ? 'Rotacja nie pokrywa całego okresu - są przerwy' 
                    : 'Rotacja nie pokrywa całego okresu'),
            'rotations' => $rotations->map(function ($r) {
                return [
                    'id' => $r->id,
                    'start_date' => $r->start_date->format('Y-m-d'),
                    'end_date' => $r->end_date->format('Y-m-d'),
                    'status' => $r->status ?? 'active'
                ];
            })->toArray()
        ];
    }

    public function render()
    {
        return view('livewire.employee-availability-checker');
    }
}
