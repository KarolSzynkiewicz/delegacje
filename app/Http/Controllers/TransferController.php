<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\AccommodationAssignment;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\TransferService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', 'id');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $employeeSearch = trim((string) $request->query('employee_search', ''));
        $vehicleFilter = $request->query('vehicle_id'); // int|string|null; supports "none"
        $transport = $request->query('transport'); // "vehicle"|"no_vehicle"|null

        $allowedSorts = ['id', 'event_date', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $query = LogisticsEvent::where('type', LogisticsEventType::TRANSFER)
            ->with([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'driverAdjustments.employee',
            ])
            ->when($employeeSearch !== '', function ($q) use ($employeeSearch) {
                $s = mb_strtolower($employeeSearch);
                $q->whereHas('participants.employee', function ($e) use ($s) {
                    $e->whereRaw('LOWER(CONCAT(first_name, " ", last_name)) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(CONCAT(last_name, " ", first_name)) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ['%'.$s.'%'])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ['%'.$s.'%']);
                });
            })
            ->when($transport === 'vehicle', fn ($q) => $q->whereNotNull('vehicle_id'))
            ->when($transport === 'no_vehicle', fn ($q) => $q->whereNull('vehicle_id'))
            ->when($vehicleFilter === 'none', fn ($q) => $q->whereNull('vehicle_id'))
            ->when(is_numeric($vehicleFilter), fn ($q) => $q->where('vehicle_id', (int) $vehicleFilter))
            ->orderBy($sort, $dir);

        $transfers = $query->paginate(20)->withQueryString();

        $vehicles = Vehicle::where('type', 'company_vehicle')->orderBy('registration_number')->get();

        return view('transfers.index', compact('transfers', 'sort', 'dir', 'vehicles', 'employeeSearch', 'vehicleFilter', 'transport'));
    }

    public function create(): View
    {
        return view('transfers.create');
    }

    public function show(LogisticsEvent $transfer): View
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        $transfer->load([
            'vehicle',
            'fromLocation',
            'toLocation',
            'creator',
            'participants.employee',
            'participants.assignment' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    ProjectAssignment::class => ['project.location'],
                    AccommodationAssignment::class => ['accommodation.location'],
                    VehicleAssignment::class => ['vehicle'],
                ]);
            },
            'driverAdjustments.employee',
            'driverAdjustments.payroll',
        ]);

        $waypointIds = array_values(array_filter(array_map('intval', (array) ($transfer->route_waypoints ?? []))));
        $waypointsById = empty($waypointIds)
            ? collect()
            : Location::whereIn('id', $waypointIds)->get()->keyBy('id');

        $orderedWaypoints = collect();
        foreach ($waypointIds as $id) {
            if ($waypointsById->has($id)) {
                $orderedWaypoints->push($waypointsById->get($id));
            }
        }

        $routeStops = collect()
            ->when($transfer->fromLocation, fn ($c) => $c->push($transfer->fromLocation))
            ->concat($orderedWaypoints)
            ->when($transfer->toLocation, fn ($c) => $c->push($transfer->toLocation));

        return view('transfers.show', [
            'transfer' => $transfer,
            'routeStops' => $routeStops,
            'waypoints' => $orderedWaypoints,
        ]);
    }

    public function cancel(LogisticsEvent $transfer): RedirectResponse
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        if (! in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Tego transferu nie można anulować.');
        }

        if ($transfer->has_reassignment) {
            $this->transferService->reverseTransfer($transfer);
        }

        $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', $transfer->has_reassignment
                ? 'Transfer został anulowany. Przypisania zostały przywrócone.'
                : 'Transfer został anulowany.');
    }
}
