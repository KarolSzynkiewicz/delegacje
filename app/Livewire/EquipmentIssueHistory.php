<?php

namespace App\Livewire;

use App\Enums\StockMovementType;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        $rows = $this->filteredRows($equipment);
        $perPage = 20;
        $page = $this->getPage('issuesPage');

        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['pageName' => 'issuesPage']
        );

        return view('livewire.equipment-issue-history', [
            'equipment' => $equipment,
            'entries' => $paginator,
        ]);
    }

    /**
     * @return Collection<int, array{kind: string, issue: ?EquipmentIssue, movement: ?EquipmentStockMovement, employee: string, sku: string, warehouse: string, quantity: int, date: string, status: string, dispatch: string, sort_at: int}>
     */
    private function filteredRows(Equipment $equipment): Collection
    {
        $rows = $this->issueRows($equipment)->concat($this->consumptionRows($equipment));

        if (filled($this->search)) {
            $term = mb_strtolower(trim($this->search));
            $rows = $rows->filter(function (array $row) use ($term) {
                $haystack = mb_strtolower(implode(' ', [
                    $row['employee'],
                    $row['sku'],
                    $row['warehouse'],
                    $row['status'],
                    $row['dispatch'],
                    $row['issue']?->notes ?? '',
                    $row['movement']?->notes ?? '',
                    $row['movement']?->destinationMeta() ?? '',
                ]));

                return str_contains($haystack, $term);
            });
        }

        $direction = $this->sortDirection === 'asc' ? 1 : -1;

        return $rows
            ->sort(function (array $left, array $right) use ($direction) {
                $field = match ($this->sortField) {
                    'employee' => 'employee',
                    'sku' => 'sku',
                    'warehouse' => 'warehouse',
                    'quantity' => 'quantity',
                    'status' => 'status',
                    'dispatch' => 'dispatch',
                    default => 'sort_at',
                };

                return $field === 'quantity' || $field === 'sort_at'
                    ? $direction * ((int) $left[$field] <=> (int) $right[$field])
                    : $direction * strcmp((string) $left[$field], (string) $right[$field]);
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function issueRows(Equipment $equipment): Collection
    {
        return EquipmentIssue::query()
            ->where('equipment_id', $equipment->id)
            ->with(['employee', 'variant.equipment', 'warehouse.location', 'dispatch', 'equipment'])
            ->get()
            ->map(function (EquipmentIssue $issue) {
                $happenedAt = $issue->issue_date?->startOfDay() ?? $issue->created_at;

                return [
                    'kind' => 'issue',
                    'issue' => $issue,
                    'movement' => null,
                    'employee' => $issue->employee?->full_name ?? '',
                    'sku' => $issue->variant?->sku ?? $issue->variant?->kind_label ?? '',
                    'warehouse' => $issue->warehouse?->display_name ?? '',
                    'quantity' => (int) $issue->quantity_issued,
                    'date' => $happenedAt?->format('Y-m-d') ?? '',
                    'status' => $issue->statusLabel(),
                    'dispatch' => $issue->dispatch?->number ?? '',
                    'sort_at' => $issue->created_at?->timestamp ?? $happenedAt?->timestamp ?? 0,
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function consumptionRows(Equipment $equipment): Collection
    {
        return EquipmentStockMovement::query()
            ->where('equipment_id', $equipment->id)
            ->where('type', StockMovementType::CONSUMPTION)
            ->with(['employee', 'variant.equipment', 'warehouse.location', 'consumedFor', 'creator'])
            ->get()
            ->map(function (EquipmentStockMovement $movement) {
                return [
                    'kind' => 'consumption',
                    'issue' => null,
                    'movement' => $movement,
                    'employee' => $movement->destinationLabel() ?? $movement->employee?->full_name ?? '',
                    'sku' => $movement->variant?->sku ?? $movement->variant?->kind_label ?? '',
                    'warehouse' => $movement->warehouse?->display_name ?? '',
                    'quantity' => (int) $movement->quantity,
                    'date' => $movement->created_at?->format('Y-m-d') ?? '',
                    'status' => 'Rozchód',
                    'dispatch' => '',
                    'sort_at' => $movement->created_at?->timestamp ?? 0,
                ];
            });
    }
}
