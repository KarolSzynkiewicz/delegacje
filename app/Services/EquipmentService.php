<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\Employee;
use App\Models\ProjectAssignment;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Service for managing equipment issues and returns.
 */
class EquipmentService
{
    /**
     * Issue equipment to an employee.
     * 
     * @param Equipment $equipment
     * @param Employee $employee
     * @param array $data [
     *   'quantity_issued' => int,
     *   'issue_date' => string (Y-m-d),
     *   'expected_return_date' => string|null (Y-m-d),
     *   'project_assignment_id' => int|null,
     *   'notes' => string|null
     * ]
     * @return EquipmentIssue
     * @throws ValidationException
     */
    public function issueEquipment(Equipment $equipment, Employee $employee, array $data): EquipmentIssue
    {
        $quantityIssued = $data['quantity_issued'] ?? 1;
        $issueDate = Carbon::parse($data['issue_date']);

        // Validate available quantity
        if ($equipment->available_quantity < $quantityIssued) {
            throw ValidationException::withMessages([
                'quantity_issued' => "Niewystarczająca ilość sprzętu w magazynie. Dostępne: {$equipment->available_quantity}, żądane: {$quantityIssued}."
            ]);
        }

        // Create equipment issue
        return EquipmentIssue::create([
            'equipment_id' => $equipment->id,
            'employee_id' => $employee->id,
            'project_assignment_id' => $data['project_assignment_id'] ?? null,
            'quantity_issued' => $quantityIssued,
            'issue_date' => $issueDate,
            'expected_return_date' => isset($data['expected_return_date']) ? Carbon::parse($data['expected_return_date']) : null,
            'status' => 'issued',
            'notes' => $data['notes'] ?? null,
            'issued_by' => auth()->id(),
        ]);
    }

    /**
     * Return equipment from an employee.
     * 
     * @param EquipmentIssue $equipmentIssue
     * @param array $data [
     *   'return_date' => string (Y-m-d),
     *   'notes' => string|null
     * ]
     * @return bool
     */
    public function returnEquipment(EquipmentIssue $equipmentIssue, array $data): bool
    {
        $returnDate = Carbon::parse($data['return_date']);

        $equipmentIssue->markAsReturned($returnDate, auth()->id());

        if (isset($data['notes'])) {
            $equipmentIssue->update(['notes' => $data['notes']]);
        }

        return true;
    }

    /**
     * Get required equipment for a role.
     * 
     * @param int $roleId
     * @return \Illuminate\Support\Collection
     */
    public function getRequiredEquipmentForRole(int $roleId): \Illuminate\Support\Collection
    {
        return Equipment::whereHas('requirements', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    /**
     * Check if employee has all required equipment for a role.
     * 
     * @param Employee $employee
     * @param int $roleId
     * @return array ['has_all' => bool, 'missing' => array]
     */
    public function checkEmployeeEquipmentForRole(Employee $employee, int $roleId): array
    {
        $requiredEquipment = $this->getRequiredEquipmentForRole($roleId);
        $missing = [];

        foreach ($requiredEquipment as $equipment) {
            $requirement = $equipment->requirements()->where('role_id', $roleId)->first();
            $requiredQuantity = $requirement ? $requirement->required_quantity : 1;

            $issuedQuantity = EquipmentIssue::where('equipment_id', $equipment->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'issued')
                ->sum('quantity_issued');

            if ($issuedQuantity < $requiredQuantity) {
                $missing[] = [
                    'equipment' => $equipment,
                    'required' => $requiredQuantity,
                    'issued' => $issuedQuantity,
                ];
            }
        }

        return [
            'has_all' => empty($missing),
            'missing' => $missing,
        ];
    }
}
