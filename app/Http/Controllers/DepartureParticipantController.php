<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Http\Requests\DepartureParticipantRequest;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Services\DepartureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartureParticipantController extends Controller
{
    public function __construct(
        protected DepartureService $departureService
    ) {}

    public function create(LogisticsEvent $departure): View|RedirectResponse
    {
        if ($redirect = $this->guardDeparture($departure)) {
            return $redirect;
        }

        return view('departures.participants.form', [
            'departure' => $departure,
            'employee' => null,
            'isEdit' => false,
        ]);
    }

    public function store(DepartureParticipantRequest $request, LogisticsEvent $departure): RedirectResponse
    {
        if ($redirect = $this->guardDeparture($departure)) {
            return $redirect;
        }

        try {
            $this->departureService->addParticipant($departure, $request->validated());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $employee = Employee::find((int) $request->validated('employee_id'));

        return redirect()
            ->route('departures.show', $departure)
            ->with('success', 'Dopisano uczestnika'.($employee ? ': '.$employee->full_name : '').'.');
    }

    public function edit(LogisticsEvent $departure, Employee $employee): View|RedirectResponse
    {
        if ($redirect = $this->guardDeparture($departure)) {
            return $redirect;
        }

        if (! $departure->participants()->where('employee_id', $employee->id)->exists()) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Ta osoba nie jest uczestnikiem tego wyjazdu.');
        }

        return view('departures.participants.form', [
            'departure' => $departure,
            'employee' => $employee,
            'isEdit' => true,
        ]);
    }

    public function update(DepartureParticipantRequest $request, LogisticsEvent $departure, Employee $employee): RedirectResponse
    {
        if ($redirect = $this->guardDeparture($departure)) {
            return $redirect;
        }

        try {
            $this->departureService->updateParticipantAssignments(
                $departure,
                (int) $employee->id,
                $request->validated()
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('departures.show', $departure)
            ->with('success', 'Zaktualizowano przypisania: '.$employee->full_name.'.');
    }

    protected function guardDeparture(LogisticsEvent $departure): ?RedirectResponse
    {
        if ($departure->type !== LogisticsEventType::DEPARTURE) {
            abort(404);
        }

        if (! in_array($departure->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            return redirect()
                ->route('departures.show', $departure)
                ->with('error', 'Tego wyjazdu nie można edytować.');
        }

        return null;
    }
}
