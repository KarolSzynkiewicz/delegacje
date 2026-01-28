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
     * @param int $quantityIssued
     * @param Carbon $issueDate
     * @param Carbon|null $expectedReturnDate
     * @param ProjectAssignment|null $projectAssignment
     * @param string|null $notes
     * @return EquipmentIssue
     * @throws ValidationException
     */
    public function issueEquipment(
        Equipment $equipment,
        Employee $employee,
        int $quantityIssued,
        Carbon $issueDate,
        ?Carbon $expectedReturnDate = null,
        ?ProjectAssignment $projectAssignment = null,
        ?string $notes = null
    ): EquipmentIssue {
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
            'project_assignment_id' => $projectAssignment?->id,
            'quantity_issued' => $quantityIssued,
            'issue_date' => $issueDate,
            'expected_return_date' => $expectedReturnDate,
            'status' => 'issued',
            'notes' => $notes,
            'issued_by' => auth()->id(),
        ]);
    }

    /**
     * Return equipment from an employee.
     * 
     * @param EquipmentIssue $equipmentIssue
     * @param Carbon $returnDate
     * @param string $status Status: 'returned', 'damaged', 'lost'
     * @param string|null $notes
     * @return bool
     */
    public function returnEquipment(
        EquipmentIssue $equipmentIssue,
        Carbon $returnDate,
        string $status = 'returned',
        ?string $notes = null
    ): bool {
        // Validate status
        if (!in_array($status, ['returned', 'damaged', 'lost'])) {
            throw new \InvalidArgumentException("Invalid status: {$status}. Must be 'returned', 'damaged', or 'lost'.");
        }

        // Check if equipment is returnable
        if (!$equipmentIssue->equipment->returnable) {
            throw ValidationException::withMessages([
                'equipment' => 'Ten sprzęt nie może być zwracany, zgłaszany jako uszkodzony lub zgubiony.'
            ]);
        }

        $equipmentIssue->markAsReturned($returnDate, auth()->id(), $status);

        if ($notes !== null) {
            $equipmentIssue->update(['notes' => $notes]);
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
