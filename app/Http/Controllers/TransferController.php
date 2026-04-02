<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\Location;
use App\Models\LogisticsEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', 'id');
        $dir = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'event_date', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        $transfers = LogisticsEvent::where('type', LogisticsEventType::TRANSFER)
            ->with([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'driverAdjustments.employee',
            ])
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('transfers.index', compact('transfers', 'sort', 'dir'));
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

        $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', 'Transfer został anulowany.');
    }
}
