<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeePicker extends Component
{
    use WithPagination;

    public array $selectedEmployeeIds = [];

    public string $employeeSearch = '';

    public function updatedEmployeeSearch(): void
    {
        $this->resetPage('employeePickerPage');
    }

    public function toggleEmployee(int $id): void
    {
        if (in_array($id, $this->selectedEmployeeIds, true)) {
            $this->selectedEmployeeIds = array_values(
                array_filter($this->selectedEmployeeIds, fn ($v) => $v !== $id)
            );
        } else {
            $this->selectedEmployeeIds[] = $id;
        }

        $this->dispatch('transfer-employees-updated', employeeIds: $this->selectedEmployeeIds);
    }

    protected function employeesQuery()
    {
        $query = Employee::query()->orderBy('last_name')->orderBy('first_name');

        $term = trim($this->employeeSearch);
        if ($term !== '') {
            $escaped = addcslashes(mb_strtolower($term), '%_\\');
            $like = '%'.$escaped.'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(COALESCE(first_name, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(last_name, "")) LIKE ?', [$like])
                    ->orWhereRaw(
                        'LOWER(TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, "")))) LIKE ?',
                        [$like]
                    );
            });
        }

        return $query;
    }

    public function render()
    {
        return view('livewire.employee-picker', [
            'employees' => $this->employeesQuery()->paginate(16, ['*'], 'employeePickerPage'),
        ]);
    }
}
