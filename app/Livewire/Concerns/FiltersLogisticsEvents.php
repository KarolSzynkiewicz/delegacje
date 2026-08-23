<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait FiltersLogisticsEvents
{
    public string $employeeSearch = '';

    public string $transport = '';

    public string $vehicleFilter = '';

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    protected function logisticsQueryString(): array
    {
        return [
            'employeeSearch' => ['except' => '', 'as' => 'employee_search'],
            'transport' => ['except' => ''],
            'vehicleFilter' => ['except' => '', 'as' => 'vehicle_id'],
            'sortField' => ['except' => 'id', 'as' => 'sort'],
            'sortDirection' => ['except' => 'desc', 'as' => 'dir'],
        ];
    }

    public function updatingEmployeeSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTransport(): void
    {
        $this->resetPage();
    }

    public function updatingVehicleFilter(): void
    {
        $this->resetPage();
    }

    public function clearLogisticsFilters(): void
    {
        $this->employeeSearch = '';
        $this->transport = '';
        $this->vehicleFilter = '';
        $this->sortField = 'id';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    protected function applyLogisticsEventFilters(Builder $query): void
    {
        if ($this->employeeSearch !== '') {
            $s = mb_strtolower($this->employeeSearch);
            $query->whereHas('participants.employee', function ($e) use ($s) {
                $e->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ['%'.$s.'%'])
                    ->orWhereRaw('LOWER(CONCAT(last_name, " ", first_name)) LIKE ?', ['%'.$s.'%'])
                    ->orWhereRaw('LOWER(first_name) LIKE ?', ['%'.$s.'%'])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.$s.'%'])
                    ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$s.'%']);
            });
        }

        if ($this->transport === 'vehicle') {
            $query->whereNotNull('vehicle_id');
        } elseif ($this->transport === 'no_vehicle') {
            $query->whereNull('vehicle_id');
        }

        if ($this->vehicleFilter === 'none') {
            $query->whereNull('vehicle_id');
        } elseif (is_numeric($this->vehicleFilter)) {
            $query->where('vehicle_id', (int) $this->vehicleFilter);
        }
    }

    protected function applyLogisticsEventSort(Builder $query, array $allowedSorts = ['id', 'event_date', 'created_at']): void
    {
        $sort = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'id';
        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sort, $dir);

        if ($sort !== 'id') {
            $query->orderBy('id', 'desc');
        }
    }

    public function hasLogisticsFilters(): bool
    {
        return $this->employeeSearch !== '' || $this->transport !== '' || $this->vehicleFilter !== '';
    }

    public function clearFilters(): void
    {
        $this->clearLogisticsFilters();
    }

    public function sortBy(string $field): void
    {
        $allowed = $this->logisticsSortableFields();

        if (! in_array($field, $allowed, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    protected function logisticsSortableFields(): array
    {
        return ['id', 'event_date', 'created_at'];
    }
}
