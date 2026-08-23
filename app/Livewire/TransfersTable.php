<?php

namespace App\Livewire;

use App\Enums\LogisticsEventType;
use App\Livewire\Concerns\FiltersLogisticsEvents;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

class TransfersTable extends Component
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
        $query = LogisticsEvent::where('type', LogisticsEventType::TRANSFER)
            ->with([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'driverAdjustments.employee',
            ]);

        $this->applyLogisticsEventFilters($query);
        $this->applyLogisticsEventSort($query);

        $transfers = $query->paginate(20);

        return view('livewire.transfers-table', [
            'transfers' => $transfers,
            'vehicles' => Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get(),
        ]);
    }
}
