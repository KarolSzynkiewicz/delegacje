<?php

namespace App\Rules;

use App\Models\Rotation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RotationDoesNotOverlap implements ValidationRule
{
    protected $employeeId;
    protected $rotationId;

    /**
     * Create a new rule instance.
     *
     * @param  int|null  $employeeId
     * @param  int|null  $rotationId
     */
    public function __construct($employeeId, $rotationId = null)
    {
        $this->employeeId = $employeeId;
        $this->rotationId = $rotationId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->employeeId) {
            return;
        }

        $startDate = request()->input('start_date');
        $endDate = $value;

        if (!$startDate || !$endDate) {
            return;
        }

        // Sprawdź czy istnieją nakładające się rotacje (aktywne, zaplanowane lub zakończone)
        // Wykluczamy tylko anulowane (cancelled)
        $overlappingRotations = Rotation::where('employee_id', $this->employeeId)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'cancelled');
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            });

        // Wyklucz aktualną rotację przy edycji
        if ($this->rotationId) {
            $overlappingRotations->where('id', '!=', $this->rotationId);
        }

        if ($overlappingRotations->exists()) {
            $fail('Rotacja nakłada się z istniejącą aktywną rotacją tego pracownika.');
        }
    }
}
