<?php

namespace App\Livewire;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class EmployeePicker extends Component
{
    use WithoutUrlPagination;
    use WithPagination;

    public array $selectedEmployeeIds = [];

    public string $employeeSearch = '';

    public string $label = 'Uczestnicy';

    public string $notifyEvent = 'transfer-employees-updated';

    public bool $excludeTerminated = false;

    public bool $showCard = true;

    public bool $required = false;

    public function updatedEmployeeSearch(): void
    {
        $this->resetPage('employeePickerPage');
    }

    public function toggleEmployee(int $id): void
    {
        $id = (int) $id;
        $current = $this->normalizedSelectedIds();

        if (in_array($id, $current, true)) {
            $this->selectedEmployeeIds = array_values(
                array_filter($current, fn ($value) => $value !== $id)
            );
        } else {
            $current[] = $id;
            $this->selectedEmployeeIds = $current;
        }

        $this->dispatch('employees-updated', employeeIds: $this->selectedEmployeeIds);
        $this->dispatch($this->notifyEvent, employeeIds: $this->selectedEmployeeIds);
    }

    public function paginationView(): string
    {
        return 'livewire.partials.employee-picker-pagination';
    }

    public function render()
    {
        $selectedIds = $this->normalizedSelectedIds();

        return view('livewire.employee-picker', [
            'employees' => $this->employeesQuery()->paginate(16, ['*'], 'employeePickerPage'),
            'selectedIds' => $selectedIds,
            'selectedEmployees' => $this->selectedEmployees($selectedIds),
        ]);
    }

    /**
     * @return list<int>
     */
    private function normalizedSelectedIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->selectedEmployeeIds)));
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Employee>
     */
    private function selectedEmployees(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function employeesQuery()
    {
        $query = Employee::query()->orderBy('last_name')->orderBy('first_name');

        if ($this->excludeTerminated) {
            $query->whereNull('terminated_at');
        }

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
}
