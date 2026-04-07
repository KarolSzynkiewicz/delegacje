<?php

namespace App\Services;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Models\AccommodationAssignment;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\ProjectAssignment;
use App\Models\Rotation;
use App\Models\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RotationFieldHistoryService
{
    /**
     * Oś czasu delegacji w oknie rotacji: wydarzenia logistyczne + przypisania z linkami (tylko odczyt).
     *
     * @return Collection<int, array{at: Carbon, label: string, description: ?string, url: ?string, sort: string}>
     */
    public function timelineForRotation(Employee $employee, Rotation $rotation): Collection
    {
        $start = $rotation->start_date->copy()->startOfDay();
        $end = $rotation->end_date->copy()->endOfDay();

        $items = collect();

        $events = LogisticsEvent::query()
            ->whereHas('participants', fn ($q) => $q->where('employee_id', $employee->id))
            ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN, LogisticsEventType::TRANSFER])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where('event_date', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start);
            })
            ->with(['fromLocation', 'toLocation'])
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        foreach ($events as $event) {
            $items->push($this->formatLogisticsEvent($event));
        }

        $overlap = function ($q) use ($rotation) {
            $q->where('start_date', '<=', $rotation->end_date)
                ->where(fn ($q2) => $q2->whereNull('end_date')->orWhere('end_date', '>=', $rotation->start_date));
        };

        ProjectAssignment::query()
            ->where('employee_id', $employee->id)
            ->where($overlap)
            ->with('project')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->each(fn (ProjectAssignment $pa) => $items->push($this->formatProjectAssignment($pa)));

        AccommodationAssignment::query()
            ->where('employee_id', $employee->id)
            ->where($overlap)
            ->with(['accommodation.location'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->each(fn (AccommodationAssignment $aa) => $items->push($this->formatAccommodationAssignment($aa)));

        VehicleAssignment::query()
            ->where('employee_id', $employee->id)
            ->where($overlap)
            ->with('vehicle')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->each(fn (VehicleAssignment $va) => $items->push($this->formatVehicleAssignment($va)));

        return $items->sortBy('sort')->values();
    }

    /**
     * @return array{at: Carbon, label: string, description: ?string, url: ?string, sort: string}
     */
    protected function formatLogisticsEvent(LogisticsEvent $event): array
    {
        $at = $event->event_date->copy();

        $from = $event->fromLocation?->name;
        $to = $event->toLocation?->name;
        $route = $from && $to ? "{$from} → {$to}" : ($to ?? $from ?? '');

        $extra = '';
        if ($event->type === LogisticsEventType::TRANSFER) {
            $extra = $event->has_reassignment
                ? ' (zmiana przypisań)'
                : ' (bez zmiany przypisań)';
        }

        $label = match ($event->type) {
            LogisticsEventType::DEPARTURE => 'Wyjazd',
            LogisticsEventType::RETURN => 'Zjazd / powrót',
            LogisticsEventType::TRANSFER => 'Transfer'.$extra,
            default => 'Wydarzenie logistyczne',
        };

        $url = match ($event->type) {
            LogisticsEventType::DEPARTURE => route('departures.show', $event),
            LogisticsEventType::RETURN => route('return-trips.show', $event),
            LogisticsEventType::TRANSFER => route('transfers.show', $event),
            default => null,
        };

        return [
            'at' => $at,
            'label' => $label,
            'description' => $route !== '' ? $route : null,
            'url' => $url,
            'sort' => $at->format('Y-m-d H:i:s').'-e-'.$event->id,
        ];
    }

    /**
     * @return array{at: Carbon, label: string, description: ?string, url: ?string, sort: string}
     */
    protected function formatProjectAssignment(ProjectAssignment $pa): array
    {
        $at = $pa->start_date->copy()->startOfDay();
        $projectName = $pa->project?->name ?? 'Projekt #'.$pa->project_id;
        $range = $pa->start_date->format('Y-m-d')
            .' — '
            .($pa->end_date ? $pa->end_date->format('Y-m-d') : '…');

        return [
            'at' => $at,
            'label' => 'Przypisanie do projektu',
            'description' => $projectName.' · '.$range,
            'url' => route('project-assignments.show', $pa),
            'sort' => $at->format('Y-m-d H:i:s').'-pa-'.$pa->id,
        ];
    }

    /**
     * @return array{at: Carbon, label: string, description: ?string, url: ?string, sort: string}
     */
    protected function formatAccommodationAssignment(AccommodationAssignment $aa): array
    {
        $at = $aa->start_date->copy()->startOfDay();
        $name = $aa->accommodation?->name ?? 'Mieszkanie #'.$aa->accommodation_id;
        $range = $aa->start_date->format('Y-m-d')
            .' — '
            .($aa->end_date ? $aa->end_date->format('Y-m-d') : '…');

        return [
            'at' => $at,
            'label' => 'Zakwaterowanie',
            'description' => $name.' · '.$range,
            'url' => route('accommodation-assignments.show', $aa),
            'sort' => $at->format('Y-m-d H:i:s').'-aa-'.$aa->id,
        ];
    }

    /**
     * @return array{at: Carbon, label: string, description: ?string, url: ?string, sort: string}
     */
    protected function formatVehicleAssignment(VehicleAssignment $va): array
    {
        $at = $va->start_date->copy()->startOfDay();
        $reg = $va->vehicle?->registration_number ?? 'Pojazd #'.$va->vehicle_id;
        $range = $va->start_date->format('Y-m-d')
            .' — '
            .($va->end_date ? $va->end_date->format('Y-m-d') : '…');

        return [
            'at' => $at,
            'label' => 'Przypisanie do pojazdu',
            'description' => $reg.' · '.$range,
            'url' => route('vehicle-assignments.show', $va),
            'sort' => $at->format('Y-m-d H:i:s').'-va-'.$va->id,
        ];
    }
}
