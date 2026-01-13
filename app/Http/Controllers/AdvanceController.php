<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Employee;
use App\Http\Requests\StoreAdvanceRequest;
use App\Http\Requests\UpdateAdvanceRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdvanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $advances = Advance::with('employee')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('advances.index', compact('advances'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('advances.create', compact('employees'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdvanceRequest $request): RedirectResponse
    {
        Advance::create($request->validated());

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Advance $advance): View
    {
        $advance->load('employee');
        return view('advances.show', compact('advance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Advance $advance): View
    {
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        return view('advances.edit', compact('advance', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdvanceRequest $request, Advance $advance): RedirectResponse
    {
        $advance->update($request->validated());

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Advance $advance): RedirectResponse
    {
        $advance->delete();

        return redirect()->route('advances.index')
            ->with('success', 'Zaliczka została usunięta.');
    }
}
