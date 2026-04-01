<?php

namespace App\Http\Controllers;

use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index(): View
    {
        $transfers = LogisticsEvent::where('type', LogisticsEventType::TRANSFER)
            ->with([
                'vehicle',
                'fromLocation',
                'toLocation',
                'creator',
                'participants.employee',
                'driverAdjustments.employee',
            ])
            ->orderBy('event_date', 'desc')
            ->paginate(20);

        return view('transfers.index', compact('transfers'));
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

        return view('transfers.show', compact('transfer'));
    }

    public function cancel(LogisticsEvent $transfer): RedirectResponse
    {
        abort_if($transfer->type !== LogisticsEventType::TRANSFER, 404);

        if (!in_array($transfer->status, [LogisticsEventStatus::PLANNED, LogisticsEventStatus::COMPLETED])) {
            return redirect()->route('transfers.show', $transfer)
                ->with('error', 'Tego transferu nie można anulować.');
        }

        $transfer->update(['status' => LogisticsEventStatus::CANCELLED]);

        return redirect()->route('transfers.show', $transfer)
            ->with('success', 'Transfer został anulowany.');
    }
}
