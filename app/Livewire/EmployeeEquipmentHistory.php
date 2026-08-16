<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\EquipmentIssue;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeEquipmentHistory extends Component
{
    use WithPagination;

    public int $employeeId;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

    /**
     * @var list<string>
     */
    private const SORT_FIELDS = ['sku', 'warehouse', 'quantity', 'date', 'returned', 'status', 'dispatch'];

    /**
     * @var list<string>
     */
    private const STATUS_FILTERS = ['', 'issued', 'given', 'returned', 'reserved'];

    public function mount(Employee $employee): void
    {
        $this->employeeId = $employee->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage('employeeIssuesPage');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage('employeeIssuesPage');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->resetPage('employeeIssuesPage');
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORT_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage('employeeIssuesPage');
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $employee = Employee::query()->findOrFail($this->employeeId);

        $held = EquipmentIssue::query()
            ->where('employee_id', $employee->id)
            ->where('status', EquipmentIssue::STATUS_ISSUED)
            ->with(['equipment.variants', 'variant', 'warehouse.location', 'dispatch'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        $reserved = EquipmentIssue::query()
            ->where('employee_id', $employee->id)
            ->where('status', EquipmentIssue::STATUS_RESERVED)
            ->with(['equipment.variants', 'variant', 'warehouse.location', 'dispatch'])
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->get();

        $query = EquipmentIssue::query()
            ->where('equipment_issues.employee_id', $employee->id)
            ->where('equipment_issues.status', '!=', EquipmentIssue::STATUS_UNFULFILLED)
            ->with(['equipment.variants', 'variant', 'warehouse.location', 'dispatch'])
            ->select('equipment_issues.*');

        $this->applyStatusFilter($query);

        if (filled($this->search)) {
            $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
            $query->where(function (Builder $search) use ($term) {
                $search->whereHas('equipment', fn (Builder $items) => $items->where('name', 'like', $term))
                    ->orWhereHas('variant', fn (Builder $variants) => $variants->where('value', 'like', $term))
                    ->orWhereHas('warehouse', fn (Builder $warehouses) => $warehouses->where('name', 'like', $term))
                    ->orWhereHas('dispatch', fn (Builder $dispatches) => $dispatches->where('number', 'like', $term))
                    ->orWhere('equipment_issues.notes', 'like', $term)
                    ->orWhere('equipment_issues.status', 'like', $term);
            });
        }

        $this->applySort($query);

        return view('livewire.employee-equipment-history', [
            'employee' => $employee,
            'held' => $held,
            'reserved' => $reserved,
            'issues' => $query->paginate(20, pageName: 'employeeIssuesPage'),
        ]);
    }

    private function applyStatusFilter(Builder $query): void
    {
        if (! in_array($this->statusFilter, self::STATUS_FILTERS, true) || $this->statusFilter === '') {
            return;
        }

        if ($this->statusFilter === 'returned') {
            $query->whereIn('equipment_issues.status', [
                EquipmentIssue::STATUS_RETURNED,
                EquipmentIssue::STATUS_DAMAGED,
                EquipmentIssue::STATUS_LOST,
            ]);

            return;
        }

        $query->where('equipment_issues.status', $this->statusFilter);
    }

    private function applySort(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortField) {
            'sku' => $query
                ->leftJoin('equipment', 'equipment.id', '=', 'equipment_issues.equipment_id')
                ->leftJoin('equipment_variants', 'equipment_variants.id', '=', 'equipment_issues.equipment_variant_id')
                ->orderBy('equipment.name', $direction)
                ->orderBy('equipment_variants.value', $direction),
            'warehouse' => $query
                ->leftJoin('warehouses', 'warehouses.id', '=', 'equipment_issues.warehouse_id')
                ->orderBy('warehouses.name', $direction),
            'quantity' => $query->orderBy('equipment_issues.quantity_issued', $direction),
            'returned' => $query->orderBy('equipment_issues.actual_return_date', $direction),
            'status' => $query->orderBy('equipment_issues.status', $direction),
            'dispatch' => $query
                ->leftJoin('warehouse_dispatches', 'warehouse_dispatches.id', '=', 'equipment_issues.warehouse_dispatch_id')
                ->orderBy('warehouse_dispatches.number', $direction),
            default => $query
                ->orderBy('equipment_issues.issue_date', $direction)
                ->orderBy('equipment_issues.id', $direction),
        };
    }
}
