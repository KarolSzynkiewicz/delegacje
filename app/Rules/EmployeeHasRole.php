<?php

namespace App\Rules;

use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmployeeHasRole implements ValidationRule
{
    protected $employeeId;

    /**
     * Create a new rule instance.
     *
     * @param  int|null  $employeeId
     */
    public function __construct($employeeId = null)
    {
        $this->employeeId = $employeeId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->employeeId) {
            $fail('Pracownik nie został wybrany.');
            return;
        }

        $employee = Employee::with('roles')->find($this->employeeId);

        if (!$employee) {
            $fail('Pracownik nie został znaleziony.');
            return;
        }

        // Sprawdź czy pracownik ma daną rolę
        $hasRole = $employee->roles->contains('id', $value);

        if (!$hasRole) {
            $role = \App\Models\Role::find($value);
            $roleName = $role ? $role->name : 'nieznana';
            $fail("Pracownik {$employee->full_name} nie posiada roli: {$roleName}. Nie można przypisać go do projektu z tą rolą.");
        }
    }
}
