<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Employee;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\UpdateAdjustmentRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdjustmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $adjustments = Adjustment::with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('adjustments.index', compact('adjustments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('adjustments.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdjustmentRequest $request): RedirectResponse
    {
        Adjustment::create($request->validated());

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Adjustment $adjustment): View
    {
        $adjustment->load('employee');
        return view('adjustments.show', compact('adjustment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Adjustment $adjustment): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('adjustments.edit', compact('adjustment', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdjustmentRequest $request, Adjustment $adjustment): RedirectResponse
    {
        $adjustment->update($request->validated());

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Adjustment $adjustment): RedirectResponse
    {
        $adjustment->delete();

        return redirect()->route('adjustments.index')
            ->with('success', 'Kara/nagroda została usunięta.');
    }
}
