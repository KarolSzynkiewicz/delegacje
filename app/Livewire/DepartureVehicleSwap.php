<?php

namespace App\Livewire;

use App\Enums\LogisticsEventType;
use App\Models\LogisticsEvent;
use App\Services\DepartureVehicleSwapService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DepartureVehicleSwap extends Component
{
    public int $departureId;

    public ?int $newVehicleId = null;

    /** @var list<int|string> */
    public array $confirmedExternalAssignmentIds = [];

    public function mount(int $departureId): void
    {
        $this->departureId = $departureId;
        $this->departure();
    }

    public function updatedNewVehicleId(): void
    {
        $this->confirmedExternalAssignmentIds = [];
        $this->resetErrorBag();
    }

    public function confirm(DepartureVehicleSwapService $swap): mixed
    {
        $departure = $this->departure();
        if (! $this->newVehicleId) {
            $this->addError('newVehicleId', 'Wybierz pojazd.');

            return null;
        }

        try {
            $ids = array_map('intval', $this->confirmedExternalAssignmentIds);
            $swap->swap($departure, (int) $this->newVehicleId, $ids);
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->unique()->values();
            foreach ($messages as $message) {
                $this->addError('newVehicleId', $message);
            }

            return null;
        }

        return redirect()
            ->route('departures.show', $departure)
            ->with('success', 'Pojazd wyjazdu został zmieniony. Data wyjazdu bez zmian.');
    }

    public function render(DepartureVehicleSwapService $swap)
    {
        $departure = $this->departure();
        $blockers = $swap->swapBlockers($departure);
        $candidates = $blockers === [] ? $swap->candidateVehicles($departure) : collect();
        $preview = $swap->preview(
            $departure,
            $this->newVehicleId ? (int) $this->newVehicleId : null,
            array_map('intval', $this->confirmedExternalAssignmentIds)
        );

        return view('livewire.departure-vehicle-swap', [
            'departure' => $departure,
            'blockers' => $blockers,
            'candidates' => $candidates,
            'preview' => $preview,
            'canConfirm' => $this->newVehicleId
                && $preview['blockers'] === []
                && $blockers === [],
        ]);
    }

    protected function departure(): LogisticsEvent
    {
        $departure = LogisticsEvent::query()
            ->with(['vehicle', 'participants.employee', 'vehicleAssignments.employee'])
            ->findOrFail($this->departureId);

        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        return $departure;
    }
}
