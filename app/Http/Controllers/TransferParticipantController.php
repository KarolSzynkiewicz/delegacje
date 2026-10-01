<?php

namespace App\Http\Controllers;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\ProjectStatus;
use App\Http\Requests\TransferParticipantRequest;
use App\Models\Accommodation;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Services\TransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransferParticipantController extends Controller
{
    public function __construct(
        protected TransferService $transferService
    ) {}

    public function create(LogisticsEvent $transfer): View|RedirectResponse
    {
        if ($redirect = $this->guardTransfer($transfer)) {
            return $redirect;
        }

        if ($transfer->isReassignmentPlan()) {
            return redirect()->route('transfers.create', ['plan' => $transfer->id]);
        }

        $day = $transfer->event_date?->format('Y-m-d') ?? now()->toDateString();

        return view('transfers.participants.form', [
            'transfer' => $transfer,
            'projects' => Project::query()
                ->where('status', ProjectStatus::ACTIVE)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'accommodations' => Accommodation::query()->orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::query()
                ->where('type', 'company_vehicle')
                ->operational()
                ->orderBy('registration_number')
                ->get(['id', 'registration_number', 'brand', 'model']),
            'defaultStart' => $day,
            'existingEmployeeIds' => $transfer->participants()->pluck('employee_id')->unique()->values()->all(),
        ]);
    }

    public function store(TransferParticipantRequest $request, LogisticsEvent $transfer): RedirectResponse
    {
        if ($redirect = $this->guardTransfer($transfer)) {
            return $redirect;
        }

        try {
            $this->transferService->addParticipant($transfer, $request->validated());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $employee = Employee::find((int) $request->validated('employee_id'));

        return redirect()
            ->route('transfers.show', $transfer)
            ->with('success', 'Dopisano uczestnika'.($employee ? ': '.$employee->full_name : '').'.');
    }

    protected function guardTransfer(LogisticsEvent $transfer): ?RedirectResponse
    {
        if ($transfer->type !== LogisticsEventType::TRANSFER) {
            abort(404);
        }

        if (! in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED], true)) {
            return redirect()
                ->route('transfers.show', $transfer)
                ->with('error', 'Tego transferu nie można edytować.');
        }

        return null;
    }
}
