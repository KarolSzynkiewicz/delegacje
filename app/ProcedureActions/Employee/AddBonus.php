<?php

namespace App\ProcedureActions\Employee;

class AddBonus extends AddAdjustment
{
    public function key(): string
    {
        return 'employee.bonus';
    }

    public function label(): string
    {
        return 'Dodaj uznanie';
    }

    protected function adjustmentType(): string
    {
        return 'bonus';
    }
}
