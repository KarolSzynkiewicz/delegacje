<?php

namespace App\Livewire;

use App\Enums\LogisticsEventType;
use App\Livewire\Concerns\FiltersLogisticsEvents;
use App\Models\LogisticsEvent;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;

class ReturnTripsTable extends Component
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
        $query = LogisticsEvent::where('type', LogisticsEventType::RETURN)
            ->with([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'participants.assignment' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\VehicleAssignment::class => ['vehicle'],
                        \App\Models\ProjectAssignment::class => ['project'],
                        \App\Models\AccommodationAssignment::class => ['accommodation.location'],
                    ]);
                },
            ]);

        $this->applyLogisticsEventFilters($query);
        $this->applyLogisticsEventSort($query);

        $returnTrips = $query->paginate(20);

        return view('livewire.return-trips-table', [
            'returnTrips' => $returnTrips,
            'vehicles' => Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get(),
        ]);
    }
}
