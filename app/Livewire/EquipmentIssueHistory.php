<?php

namespace App\Livewire;

use App\Models\Equipment;
use App\Models\EquipmentIssue;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class EquipmentIssueHistory extends Component
{
    use WithPagination;

    public int $equipmentId;

    public string $search = '';

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

    /**
     * @var list<string>
     */
    private const SORT_FIELDS = ['employee', 'sku', 'warehouse', 'quantity', 'date', 'status', 'dispatch'];

    public function mount(Equipment $equipment): void
    {
        $this->equipmentId = $equipment->id;
    }

    public function updatingSearch(): void
    {
        $this->resetPage('issuesPage');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->sortField = 'date';
        $this->sortDirection = 'desc';
        $this->resetPage('issuesPage');
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

        $this->resetPage('issuesPage');
    }

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $equipment = Equipment::query()->findOrFail($this->equipmentId);

        if (! $equipment->issuable) {
            return view('livewire.equipment-issue-history', [
                'equipment' => $equipment,
                'issues' => null,
            ]);
        }

        $query = EquipmentIssue::query()
            ->where('equipment_issues.equipment_id', $equipment->id)
            ->with(['employee', 'variant', 'warehouse.location', 'dispatch'])
            ->select('equipment_issues.*');

        if (filled($this->search)) {
            $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
            $query->where(function (Builder $search) use ($term) {
                $search->whereHas('employee', function (Builder $employees) use ($term) {
                    $employees->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term]);
                })
                    ->orWhereHas('variant', fn (Builder $variants) => $variants->where('value', 'like', $term))
                    ->orWhereHas('warehouse', fn (Builder $warehouses) => $warehouses->where('name', 'like', $term))
                    ->orWhereHas('dispatch', fn (Builder $dispatches) => $dispatches->where('number', 'like', $term))
                    ->orWhere('equipment_issues.notes', 'like', $term)
                    ->orWhere('equipment_issues.status', 'like', $term);
            });
        }

        $this->applySort($query);

        return view('livewire.equipment-issue-history', [
            'equipment' => $equipment,
            'issues' => $query->paginate(20, pageName: 'issuesPage'),
        ]);
    }

    private function applySort(Builder $query): void
    {
        $direction = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        match ($this->sortField) {
            'employee' => $query
                ->leftJoin('employees', 'employees.id', '=', 'equipment_issues.employee_id')
                ->orderBy('employees.last_name', $direction)
                ->orderBy('employees.first_name', $direction),
            'sku' => $query
                ->leftJoin('equipment_variants', 'equipment_variants.id', '=', 'equipment_issues.equipment_variant_id')
                ->orderBy('equipment_variants.value', $direction),
            'warehouse' => $query
                ->leftJoin('warehouses', 'warehouses.id', '=', 'equipment_issues.warehouse_id')
                ->orderBy('warehouses.name', $direction),
            'quantity' => $query->orderBy('equipment_issues.quantity_issued', $direction),
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
