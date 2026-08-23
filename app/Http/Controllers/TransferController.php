<?php

namespace App\Http\Controllers;

use App\Enums\LocationPurposeType;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\VehicleAssignment;
use App\Services\TransferService;
use App\Support\DepartureRoutePlan;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function index(): View
    {
        return view('transfers.index');
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
            'transportCosts',
            'relatedDeparture',
        ]);

        $groundLegTicketRows = [];
        if ($transfer->relatedDeparture && $transfer->relatedDeparture->type === LogisticsEventType::DEPARTURE) {
            $groundLegTicketRows = DepartureRoutePlan::collectPublicLegTicketRowsFromSegments(
                is_array($transfer->relatedDeparture->route_segments)
                    ? $transfer->relatedDeparture->route_segments
                    : []
            );
        }
        if ($groundLegTicketRows !== []) {
            $empIds = collect($groundLegTicketRows)->pluck('employee_id')->unique()->values()->all();
            $empNames = Employee::whereIn('id', $empIds)->pluck('full_name', 'id');
            $groundLegTicketRows = collect($groundLegTicketRows)->map(function (array $r) use ($empNames) {
                $r['employee_name'] = $empNames[$r['employee_id']] ?? ('#'.$r['employee_id']);

                return $r;
            })->values()->all();
        }

        $routeStopRows = $transfer->getRouteStopsForDetailView()->values();
        if ($routeStopRows->isEmpty()) {
            $routeStopRows = $this->fallbackTransferRouteStopRows($transfer);
        }

        $locIds = $routeStopRows->where('kind', 'extra_location')->pluck('model_id')->unique()->filter()->all();
        $routeStopLocationsById = $locIds === []
            ? collect()
            : Location::whereIn('id', $locIds)->get()->keyBy('id');

        $hubKind = null;
        if ($transfer->from_location_id && Location::matchesPurpose((int) $transfer->from_location_id, LocationPurposeType::AIRPORT)) {
            $hubKind = 'airport';
        } elseif ($transfer->from_location_id && Location::matchesPurpose((int) $transfer->from_location_id, LocationPurposeType::STATION)) {
            $hubKind = 'station';
        }

        return view('transfers.show', [
            'transfer' => $transfer,
            'routeStopRows' => $routeStopRows,
            'routeStopLocationsById' => $routeStopLocationsById,
            'routeStopCount' => $routeStopRows->count(),
            'publicHubKind' => $hubKind,
            'groundLegTicketRows' => $groundLegTicketRows,
        ]);
    }

    /**
     * Gdy brak route_waypoints (np. transport publiczny: tylko skąd/dokąd w kolumnach).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function fallbackTransferRouteStopRows(LogisticsEvent $transfer): Collection
    {
        $from = $transfer->fromLocation;
        $to = $transfer->toLocation;
        $notes = is_array($transfer->location_stop_notes) ? $transfer->location_stop_notes : [];

        if (! $from && ! $to) {
            return collect();
        }

        $rowForLocation = static function (Location $loc, int $position, array $notes): array {
            $noteKey = (string) $loc->id;
            $note = isset($notes[$noteKey]) ? trim((string) $notes[$noteKey]) : null;

            return [
                'position' => $position,
                'kind' => 'extra_location',
                'model_id' => $loc->id,
                'name' => $loc->name,
                'address_line' => trim(implode(', ', array_filter([$loc->address, $loc->city ?? null]))),
                'employees_label' => null,
                'purpose' => ($note !== null && $note !== '') ? $note : null,
            ];
        };

        if ($from && $to && (int) $from->id === (int) $to->id) {
            return collect([$rowForLocation($from, 1, $notes)]);
        }

        $rows = collect();
        $pos = 0;
        if ($from) {
            $pos++;
            $rows->push($rowForLocation($from, $pos, $notes));
        }
        if ($to && (! $from || (int) $from->id !== (int) $to->id)) {
            $pos++;
            $rows->push($rowForLocation($to, $pos, $notes));
        }

        return $rows;
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
