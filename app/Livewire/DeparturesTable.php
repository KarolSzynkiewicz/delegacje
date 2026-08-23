<?php

namespace App\Livewire;

use App\Enums\LogisticsEventType;
use App\Livewire\Concerns\FiltersLogisticsEvents;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

class DeparturesTable extends Component
{
    use FiltersLogisticsEvents;
    use WithPagination;

    protected $queryString = [
        'employeeSearch' => ['except' => '', 'as' => 'employee_search'],
        'transport' => ['except' => ''],
        'vehicleFilter' => ['except' => '', 'as' => 'vehicle_id'],
        'sortField' => ['except' => 'id', 'as' => 'sort'],
        'sortDirection' => ['except' => 'desc', 'as' => 'dir'],
    ];

    public function paginationView(): string
    {
        return 'vendor.livewire.simple-pagination';
    }

    public function render()
    {
        $query = LogisticsEvent::where('type', LogisticsEventType::DEPARTURE)
            ->with(['vehicle', 'fromLocation', 'toLocation', 'creator', 'participants.employee']);

        $this->applyLogisticsEventFilters($query);
        $this->applyLogisticsEventSort($query);

        $departures = $query->paginate(20);

        return view('livewire.departures-table', [
            'departures' => $departures,
            'vehicles' => Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get(),
        ]);
    }
}
