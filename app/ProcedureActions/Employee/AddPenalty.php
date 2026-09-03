<?php

namespace App\ProcedureActions\Employee;

class AddPenalty extends AddAdjustment
{
    public function key(): string
    {
        return 'employee.penalty';
    }

    public function label(): string
    {
        return 'Dodaj obciążenie';
    }

    protected function adjustmentType(): string
    {
        return 'penalty';
    }
}
