<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\VehiclePosition;
use App\Enums\VehicleType;
use App\Models\Adjustment;
use App\Models\LogisticsEvent;
use App\Models\TransportCost;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Models\VehicleRepair;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartureVehicleSwapService
{
    public function __construct(
        protected VehicleValidationService $vehicleValidationService,
        protected LocationTrackingService $locationTrackingService
    ) {}

    public function canSwap(LogisticsEvent $departure): bool
    {
        return $this->swapBlockers($departure) === [];
    }

    /**
     * @return list<string>
     */
    public function swapBlockers(LogisticsEvent $departure): array
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            return ['Zdarzenie nie jest wyjazdem.'];
        }

        if (! in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            return ['Można zmienić auto tylko na aktywnym wyjeździe.'];
        }

        if (! $departure->vehicle_id) {
            return ['Podmiana auta dotyczy tylko transportu własnego. Ten wyjazd nie ma przypisanego pojazdu.'];
        }

        $visual = $departure->getVisualStatus();
        if ($visual === 'zakończone') {
            return ['Po dacie przyjazdu zwykła podmiana auta jest zablokowana. To już historia przejazdu — ewentualna korekta to osobna operacja.'];
        }

        if ($visual === 'anulowany') {
            return ['Nie można zmienić auta na anulowanym wyjeździe.'];
        }

        return [];
    }

    /**
     * @return Collection<int, Vehicle>
     */
    public function candidateVehicles(LogisticsEvent $departure): Collection
    {
        $this->assertCanSwap($departure);

        $travelStart = $departure->event_date->copy()->startOfDay();
        $travelEnd = ($departure->end_date ?? $departure->event_date)->copy()->startOfDay();

        return Vehicle::query()
            ->whereIn('type', [VehicleType::COMPANY_VEHICLE, VehicleType::RENTAL])
            ->where('id', '!=', $departure->vehicle_id)
            ->orderBy('registration_number')
            ->get()
            ->filter(function (Vehicle $vehicle) use ($travelStart, $travelEnd, $departure) {
                if (! $this->isAtBaseOn($vehicle, $travelStart)) {
                    return false;
                }

                $result = $this->vehicleValidationService->validateForLogisticsEvent(
                    $vehicle,
                    $travelStart,
                    $travelEnd,
                    $departure->id,
                    true
                );

                return $result['valid'];
            })
            ->values();
    }

    /**
     * @param  list<int>  $confirmedExternalAssignmentIds
     * @return array{
     *     blockers: list<string>,
     *     warnings: list<string>,
     *     from: array<string, mixed>|null,
     *     to: array<string, mixed>|null,
     *     participant_count: int,
     *     driver: array{type: string, name: string|null, employee_id: int|null},
     *     participant_assignments: list<array<string, mixed>>,
     *     external_assignments: list<array<string, mixed>>,
     *     travel_ok: bool,
     *     stay_ok: bool,
     * }
     */
    public function preview(LogisticsEvent $departure, ?int $newVehicleId, array $confirmedExternalAssignmentIds = []): array
    {
        $blockers = $this->swapBlockers($departure);
        $from = $departure->vehicle;

        $empty = [
            'blockers' => $blockers,
            'warnings' => [],
            'from' => $from ? $this->vehiclePayload($from) : null,
            'to' => null,
            'participant_count' => $departure->participants()->count(),
            'driver' => $this->driverPayload($departure),
            'participant_assignments' => [],
            'external_assignments' => [],
            'travel_ok' => false,
            'stay_ok' => false,
        ];

        if ($blockers !== [] || ! $from) {
            return $empty;
        }

        $participantIds = $departure->participants()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        $participantAssignments = $this->participantAssignmentsToSwap($departure, $from->id, $participantIds);
        $externalAssignments = $this->externalAssignmentsOnOldVehicle($from->id, $participantIds, $participantAssignments);

        $empty['participant_assignments'] = $participantAssignments->map(fn (VehicleAssignment $a) => $this->assignmentPayload($a, false))->all();
        $empty['external_assignments'] = $externalAssignments->map(function (VehicleAssignment $a) use ($participantAssignments) {
            $overlap = $this->overlapWithAny($a, $participantAssignments);

            return $this->assignmentPayload($a, $this->isWiderThanOverlap($a, $overlap), $overlap);
        })->all();

        if (! $newVehicleId) {
            return $empty;
        }

        if ((int) $newVehicleId === (int) $from->id) {
            $empty['blockers'][] = 'Wybierz inny pojazd niż obecny.';

            return $empty;
        }

        $to = Vehicle::query()->find($newVehicleId);
        if (! $to) {
            $empty['blockers'][] = 'Nie znaleziono wybranego pojazdu.';

            return $empty;
        }

        $empty['to'] = $this->vehiclePayload($to);

        $warnings = [];
        $visual = $departure->getVisualStatus();
        if ($visual === 'w trakcie') {
            $warnings[] = 'Wyjazd już trwa (między datą wyjazdu a przyjazdu). Podmiana zapisze, którym autem naprawdę pojechali — daty zostają bez zmian.';
        }

        $travelStart = $departure->event_date->copy()->startOfDay();
        $travelEnd = ($departure->end_date ?? $departure->event_date)->copy()->startOfDay();

        $eventCheck = $this->vehicleValidationService->validateForLogisticsEvent(
            $to,
            $travelStart,
            $travelEnd,
            $departure->id,
            true
        );
        if (! $eventCheck['valid']) {
            foreach ($eventCheck['errors'] as $error) {
                $blockers[] = $error;
            }
        }

        $participantCount = count($participantIds);
        $capacity = (int) ($to->capacity ?? 0);
        $travelOk = $capacity > 0 && $participantCount <= $capacity;
        if ($capacity <= 0) {
            $blockers[] = 'Wybrany pojazd nie ma ustawionej pojemności.';
        } elseif (! $travelOk) {
            $blockers[] = "Nie można podmienić pojazdu. Wybrany pojazd ma pojemność {$capacity} osób, a wyjazd obejmuje {$participantCount} osób.";
        }

        $allowedExternalIds = $externalAssignments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $confirmedIds = array_values(array_unique(array_map('intval', $confirmedExternalAssignmentIds)));
        $unknown = array_diff($confirmedIds, $allowedExternalIds);
        if ($unknown !== []) {
            $blockers[] = 'Zaznaczenie osób spoza wyjazdu jest nieaktualne — odśwież listę i zatwierdź ponownie.';
        }

        $confirmedExternal = $externalAssignments->filter(fn (VehicleAssignment $a) => in_array((int) $a->id, $confirmedIds, true));

        $stayError = $this->stayCapacityError(
            $to,
            $participantAssignments,
            $confirmedExternal
        );
        if ($stayError !== null) {
            $blockers[] = $stayError;
        }

        $location = $this->locationTrackingService->getVehicleLocationStatus($to, $travelStart);
        if (! empty($location['in_transit']) || ! empty($location['outside_base'])) {
            $blockers[] = 'Wybrany pojazd nie jest w bazie w dniu wyjazdu.';
        }

        $repair = VehicleRepair::query()
            ->where('vehicle_id', $to->id)
            ->where('start_date', '<=', $travelEnd)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $travelStart))
            ->first();
        if ($repair) {
            $warnings[] = 'Wybrany pojazd ma w tym okresie wpis serwisowy / naprawę.';
        }

        $othersOnNew = $this->otherOccupantsOnVehicle($to->id, $participantAssignments, $confirmedExternal);
        if ($othersOnNew->isNotEmpty()) {
            $names = $othersOnNew->map(fn (VehicleAssignment $a) => $a->employee?->full_name ?? ('#'.$a->employee_id))->unique()->implode(', ');
            $warnings[] = 'Wybrany pojazd ma już inne przypisania ('.$names.'). Nie zostaną usunięte — mieszczą się w pojemności albo blokują podmianę powyżej.';
        }

        $empty['blockers'] = array_values(array_unique($blockers));
        $empty['warnings'] = $warnings;
        $empty['travel_ok'] = $travelOk && $eventCheck['valid'];
        $empty['stay_ok'] = $stayError === null;

        return $empty;
    }

    /**
     * @param  list<int>  $confirmedExternalAssignmentIds
     */
    public function swap(LogisticsEvent $departure, int $newVehicleId, array $confirmedExternalAssignmentIds = []): LogisticsEvent
    {
        $preview = $this->preview($departure, $newVehicleId, $confirmedExternalAssignmentIds);
        if ($preview['blockers'] !== []) {
            throw ValidationException::withMessages([
                'vehicle_id' => $preview['blockers'],
            ]);
        }

        $from = $departure->vehicle;
        $to = Vehicle::query()->findOrFail($newVehicleId);
        if (! $from) {
            throw ValidationException::withMessages([
                'vehicle_id' => ['Brak pojazdu na wyjeździe.'],
            ]);
        }

        $participantIds = $departure->participants()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        $participantAssignments = $this->participantAssignmentsToSwap($departure, $from->id, $participantIds);
        $externalAssignments = $this->externalAssignmentsOnOldVehicle($from->id, $participantIds, $participantAssignments);
        $confirmedIds = array_values(array_unique(array_map('intval', $confirmedExternalAssignmentIds)));
        $confirmedExternal = $externalAssignments->filter(fn (VehicleAssignment $a) => in_array((int) $a->id, $confirmedIds, true));

        return DB::transaction(function () use ($departure, $from, $to, $participantAssignments, $confirmedExternal) {
            $oldLabel = $this->vehicleLabel($from);
            $newLabel = $this->vehicleLabel($to);

            $departure->update(['vehicle_id' => $to->id]);

            foreach ($participantAssignments as $assignment) {
                $this->moveWholeAssignment($assignment, $to);
            }

            foreach ($confirmedExternal as $assignment) {
                $overlap = $this->overlapWithAny($assignment, $participantAssignments);
                if ($this->isWiderThanOverlap($assignment, $overlap) && $overlap) {
                    $this->splitMoveOverlappingSlice($assignment, $to, $overlap);
                } else {
                    $this->moveWholeAssignment($assignment, $to);
                }
            }

            TransportCost::query()
                ->where('logistics_event_id', $departure->id)
                ->where('vehicle_id', $from->id)
                ->whereIn('cost_type', ['fuel', 'parking', 'toll', 'other'])
                ->update(['vehicle_id' => $to->id]);

            $from->refresh();
            $to->refresh();
            $this->locationTrackingService->getVehicleLocationStatus($from, now());
            $this->locationTrackingService->getVehicleLocationStatus($to, now());

            $user = auth()->user();
            if ($user) {
                $externalNames = $confirmedExternal
                    ->map(fn (VehicleAssignment $a) => $a->employee?->full_name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
                $body = 'Podmiana auta: '.$oldLabel.' → '.$newLabel.'. Data wyjazdu bez zmian.';
                if ($externalNames !== '') {
                    $body .= ' Przepięto też osoby spoza wyjazdu (zatwierdzone): '.$externalNames.'.';
                }
                $departure->addComment($body, $user);
            }

            return $departure->fresh([
                'vehicle',
                'participants.employee',
                'vehicleAssignments.vehicle',
            ]);
        });
    }

    protected function assertCanSwap(LogisticsEvent $departure): void
    {
        $blockers = $this->swapBlockers($departure);
        if ($blockers !== []) {
            throw ValidationException::withMessages([
                'vehicle_id' => $blockers,
            ]);
        }
    }

    protected function isAtBaseOn(Vehicle $vehicle, Carbon $date): bool
    {
        $status = $this->locationTrackingService->getVehicleLocationStatus($vehicle, $date);

        return empty($status['in_transit']) && empty($status['outside_base']);
    }

    /**
     * @param  list<int>  $participantIds
     * @return Collection<int, VehicleAssignment>
     */
    protected function participantAssignmentsToSwap(LogisticsEvent $departure, int $oldVehicleId, array $participantIds): Collection
    {
        if ($participantIds === []) {
            return collect();
        }

        $linked = VehicleAssignment::query()
            ->excludingCancelledLogistics()
            ->where('vehicle_id', $oldVehicleId)
            ->whereIn('employee_id', $participantIds)
            ->where('logistics_event_id', $departure->id)
            ->with('employee')
            ->orderBy('start_date')
            ->get();

        if ($linked->isEmpty()) {
            return collect();
        }

        $extra = VehicleAssignment::query()
            ->excludingCancelledLogistics()
            ->where('vehicle_id', $oldVehicleId)
            ->whereIn('employee_id', $participantIds)
            ->where(function ($q) use ($departure) {
                $q->whereNull('logistics_event_id')
                    ->orWhere('logistics_event_id', '!=', $departure->id);
            })
            ->with('employee')
            ->get()
            ->filter(fn (VehicleAssignment $a) => $this->overlapsAny($a, $linked));

        return $linked->concat($extra)->unique('id')->values();
    }

    /**
     * @param  list<int>  $participantIds
     * @param  Collection<int, VehicleAssignment>  $participantAssignments
     * @return Collection<int, VehicleAssignment>
     */
    protected function externalAssignmentsOnOldVehicle(int $oldVehicleId, array $participantIds, Collection $participantAssignments): Collection
    {
        if ($participantAssignments->isEmpty()) {
            return collect();
        }

        return VehicleAssignment::query()
            ->excludingCancelledLogistics()
            ->where('vehicle_id', $oldVehicleId)
            ->whereNotIn('employee_id', $participantIds)
            ->with('employee')
            ->orderBy('start_date')
            ->get()
            ->filter(fn (VehicleAssignment $a) => $this->overlapsAny($a, $participantAssignments))
            ->values();
    }

    /**
     * @param  Collection<int, VehicleAssignment>  $others
     */
    protected function overlapsAny(VehicleAssignment $assignment, Collection $others): bool
    {
        foreach ($others as $other) {
            if ($assignment->overlapsWithModel($other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, VehicleAssignment>  $others
     * @return array{0: Carbon, 1: Carbon|null}|null
     */
    protected function overlapWithAny(VehicleAssignment $assignment, Collection $others): ?array
    {
        $pieces = [];
        foreach ($others as $other) {
            $piece = $this->intersect(
                $assignment->start_date,
                $assignment->end_date,
                $other->start_date,
                $other->end_date
            );
            if ($piece) {
                $pieces[] = $piece;
            }
        }

        if ($pieces === []) {
            return null;
        }

        $merged = $this->mergeIntervals($pieces);

        return $merged[0] ?? null;
    }

    /**
     * @param  array{0: Carbon, 1: Carbon|null}|null  $overlap
     */
    protected function isWiderThanOverlap(VehicleAssignment $assignment, ?array $overlap): bool
    {
        if ($overlap === null) {
            return false;
        }

        $start = DateRangeService::normalizeDate($assignment->start_date);
        $end = $assignment->end_date ? DateRangeService::normalizeDate($assignment->end_date) : null;
        $oStart = DateRangeService::normalizeDate($overlap[0]);
        $oEnd = $overlap[1] ? DateRangeService::normalizeDate($overlap[1]) : null;

        if ($start->lt($oStart)) {
            return true;
        }

        if ($end === null && $oEnd !== null) {
            return true;
        }

        if ($end !== null && $oEnd !== null && $end->gt($oEnd)) {
            return true;
        }

        return false;
    }

    /**
     * @param  Collection<int, VehicleAssignment>  $participantAssignments
     * @param  Collection<int, VehicleAssignment>  $confirmedExternal
     */
    protected function stayCapacityError(
        Vehicle $newVehicle,
        Collection $participantAssignments,
        Collection $confirmedExternal
    ): ?string {
        $capacity = (int) ($newVehicle->capacity ?? 0);
        if ($capacity <= 0) {
            return null;
        }

        $moving = $participantAssignments->concat($confirmedExternal);
        if ($moving->isEmpty()) {
            return null;
        }

        $excludeIds = $moving->pluck('id')->all();
        $windowStart = $moving->min('start_date');
        $windowEnd = $moving->max('end_date');
        if (! $windowStart) {
            return null;
        }

        $cursor = DateRangeService::normalizeDate($windowStart);
        $last = $windowEnd ? DateRangeService::normalizeDate($windowEnd) : $cursor->copy()->addMonths(6);

        $existing = VehicleAssignment::query()
            ->excludingCancelledLogistics()
            ->where('vehicle_id', $newVehicle->id)
            ->whereNotIn('id', $excludeIds)
            ->get();

        for ($day = $cursor->copy(); $day->lte($last); $day->addDay()) {
            $onNew = $existing->filter(fn (VehicleAssignment $a) => $a->isActiveAt($day));
            $incoming = $moving->filter(fn (VehicleAssignment $a) => $this->coversDayAfterMove($a, $participantAssignments, $day));
            $people = $onNew->concat($incoming)->unique('employee_id');
            if ($people->count() > $capacity) {
                return 'Nie można podmienić pojazdu. W dniu '.$day->format('d.m.Y').' na nowym aucie byłoby '
                    .$people->count().' osób przy pojemności '.$capacity.'.';
            }

            $drivers = $people->filter(function (VehicleAssignment $a) {
                $pos = $a->position;

                return $pos === VehiclePosition::DRIVER || $pos === VehiclePosition::DRIVER->value;
            });
            if ($drivers->unique('employee_id')->count() > 1) {
                return 'Nie można podmienić pojazdu. W dniu '.$day->format('d.m.Y').' na nowym aucie byliby dwaj kierowcy.';
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, VehicleAssignment>  $participantAssignments
     */
    protected function coversDayAfterMove(VehicleAssignment $assignment, Collection $participantAssignments, Carbon $day): bool
    {
        $overlap = $this->overlapWithAny($assignment, $participantAssignments);
        if ($overlap && $this->isWiderThanOverlap($assignment, $overlap)) {
            $start = DateRangeService::normalizeDate($overlap[0]);
            $end = $overlap[1] ? DateRangeService::normalizeDate($overlap[1]) : null;

            return $day->gte($start) && ($end === null || $day->lte($end));
        }

        return $assignment->isActiveAt($day);
    }

    /**
     * @param  Collection<int, VehicleAssignment>  $participantAssignments
     * @param  Collection<int, VehicleAssignment>  $confirmedExternal
     * @return Collection<int, VehicleAssignment>
     */
    protected function otherOccupantsOnVehicle(int $vehicleId, Collection $participantAssignments, Collection $confirmedExternal): Collection
    {
        $exclude = $participantAssignments->concat($confirmedExternal)->pluck('id')->all();
        $windowStart = $participantAssignments->min('start_date');
        $windowEnd = $participantAssignments->max('end_date');
        if (! $windowStart) {
            return collect();
        }

        return VehicleAssignment::query()
            ->excludingCancelledLogistics()
            ->where('vehicle_id', $vehicleId)
            ->whereNotIn('id', $exclude)
            ->overlappingWith($windowStart, $windowEnd)
            ->with('employee')
            ->get();
    }

    protected function moveWholeAssignment(VehicleAssignment $assignment, Vehicle $newVehicle): void
    {
        $assignment->update([
            'vehicle_id' => $newVehicle->id,
        ]);
    }

    /**
     * @param  array{0: Carbon, 1: Carbon|null}  $overlap
     */
    protected function splitMoveOverlappingSlice(VehicleAssignment $assignment, Vehicle $newVehicle, array $overlap): void
    {
        $origStart = DateRangeService::normalizeDate($assignment->start_date);
        $origEnd = $assignment->end_date ? DateRangeService::normalizeDate($assignment->end_date) : null;
        $oStart = DateRangeService::normalizeDate($overlap[0]);
        $oEnd = $overlap[1] ? DateRangeService::normalizeDate($overlap[1]) : null;

        $position = $assignment->position instanceof VehiclePosition
            ? $assignment->position
            : (VehiclePosition::tryFrom((string) $assignment->position) ?? VehiclePosition::PASSENGER);
        $notes = $assignment->notes;
        $employeeId = (int) $assignment->employee_id;
        $eventId = $assignment->logistics_event_id;
        $oldVehicleId = (int) $assignment->vehicle_id;

        $leftEnd = $oStart->copy()->subDay();
        $hasLeft = $origStart->lte($leftEnd);
        $hasRight = $origEnd === null || ($oEnd !== null && $origEnd->gt($oEnd));
        $rightStart = $oEnd ? $oEnd->copy()->addDay() : null;

        if ($hasLeft) {
            $assignment->update(['end_date' => $leftEnd]);
            $this->insertAssignmentSlice($employeeId, $newVehicle->id, $position, $oStart, $oEnd, $notes, $eventId);
            if ($hasRight && $rightStart) {
                $this->insertAssignmentSlice($employeeId, $oldVehicleId, $position, $rightStart, $origEnd, $notes, $eventId);
            }
        } else {
            $assignment->update([
                'vehicle_id' => $newVehicle->id,
                'start_date' => $oStart,
                'end_date' => $oEnd,
            ]);
            if ($hasRight && $rightStart) {
                $this->insertAssignmentSlice($employeeId, $oldVehicleId, $position, $rightStart, $origEnd, $notes, $eventId);
            }
        }
    }

    protected function insertAssignmentSlice(
        int $employeeId,
        int $vehicleId,
        VehiclePosition $position,
        Carbon $start,
        ?Carbon $end,
        ?string $notes,
        ?int $eventId
    ): void {
        VehicleAssignment::create([
            'employee_id' => $employeeId,
            'vehicle_id' => $vehicleId,
            'position' => $position,
            'start_date' => $start,
            'end_date' => $end,
            'notes' => $notes,
            'is_return_trip' => false,
            'logistics_event_id' => $eventId,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon|null}|null
     */
    protected function intersect(?Carbon $aStart, ?Carbon $aEnd, ?Carbon $bStart, ?Carbon $bEnd): ?array
    {
        if (! $aStart || ! $bStart) {
            return null;
        }

        $aStart = DateRangeService::normalizeDate($aStart);
        $bStart = DateRangeService::normalizeDate($bStart);
        $aEnd = $aEnd ? DateRangeService::normalizeDate($aEnd) : null;
        $bEnd = $bEnd ? DateRangeService::normalizeDate($bEnd) : null;

        if (! DateRangeService::overlaps($aStart, $aEnd, $bStart, $bEnd)) {
            return null;
        }

        $start = $aStart->gt($bStart) ? $aStart->copy() : $bStart->copy();
        if ($aEnd === null && $bEnd === null) {
            return [$start, null];
        }
        if ($aEnd === null) {
            return [$start, $bEnd?->copy()];
        }
        if ($bEnd === null) {
            return [$start, $aEnd->copy()];
        }

        $end = $aEnd->lt($bEnd) ? $aEnd->copy() : $bEnd->copy();

        return [$start, $end];
    }

    /**
     * @param  list<array{0: Carbon, 1: Carbon|null}>  $intervals
     * @return list<array{0: Carbon, 1: Carbon|null}>
     */
    protected function mergeIntervals(array $intervals): array
    {
        if ($intervals === []) {
            return [];
        }

        usort($intervals, fn ($a, $b) => $a[0]->timestamp <=> $b[0]->timestamp);
        $merged = [$intervals[0]];
        foreach (array_slice($intervals, 1) as $current) {
            $last = &$merged[array_key_last($merged)];
            if ($last[1] === null || DateRangeService::overlaps($last[0], $last[1], $current[0], $current[1])) {
                if ($current[1] === null) {
                    $last[1] = null;
                } elseif ($last[1] !== null && $current[1]->gt($last[1])) {
                    $last[1] = $current[1]->copy();
                }
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    protected function vehiclePayload(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'label' => $this->vehicleLabel($vehicle),
            'capacity' => $vehicle->capacity,
        ];
    }

    protected function vehicleLabel(Vehicle $vehicle): string
    {
        return trim($vehicle->registration_number.' '.$vehicle->brand.' '.$vehicle->model);
    }

    /**
     * @return array{type: string, name: string|null, employee_id: int|null}
     */
    protected function driverPayload(LogisticsEvent $departure): array
    {
        $adj = Adjustment::query()
            ->where('logistics_event_id', $departure->id)
            ->where('type', 'bonus')
            ->with('employee')
            ->orderBy('id')
            ->first();

        $departure->loadMissing('vehicleAssignments.employee');

        if ($adj?->employee) {
            return [
                'type' => 'internal',
                'name' => $adj->employee->full_name,
                'employee_id' => (int) $adj->employee_id,
            ];
        }

        $driverSeat = $departure->vehicleAssignments
            ->first(function (VehicleAssignment $a) {
                $pos = $a->position;

                return $pos === VehiclePosition::DRIVER || $pos === VehiclePosition::DRIVER->value;
            });

        if ($driverSeat?->employee) {
            return [
                'type' => 'internal',
                'name' => $driverSeat->employee->full_name,
                'employee_id' => (int) $driverSeat->employee_id,
            ];
        }

        return [
            'type' => 'external',
            'name' => null,
            'employee_id' => null,
        ];
    }

    /**
     * @param  array{0: Carbon, 1: Carbon|null}|null  $overlap
     * @return array<string, mixed>
     */
    protected function assignmentPayload(VehicleAssignment $assignment, bool $wider, ?array $overlap = null): array
    {
        $pos = $assignment->position;
        $posVal = $pos instanceof VehiclePosition ? $pos->value : (string) $pos;

        return [
            'id' => $assignment->id,
            'employee_id' => (int) $assignment->employee_id,
            'name' => $assignment->employee?->full_name ?? ('#'.$assignment->employee_id),
            'start' => $assignment->start_date?->format('d.m.Y'),
            'end' => $assignment->end_date?->format('d.m.Y') ?? 'bez końca',
            'position' => $posVal === 'driver' ? 'kierowca' : 'pasażer',
            'wider' => $wider,
            'overlap_start' => $overlap ? $overlap[0]->format('d.m.Y') : null,
            'overlap_end' => $overlap && $overlap[1] ? $overlap[1]->format('d.m.Y') : ($overlap ? 'bez końca' : null),
        ];
    }
}
