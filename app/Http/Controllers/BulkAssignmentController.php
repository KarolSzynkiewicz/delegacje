<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAssignmentController extends Controller
{
    /**
     * Store bulk assignment (project + vehicle + accommodation).
     * 
     * Authorization:
     * - Middleware checks: create.project-assignments (always required)
     * - Controller checks: create.vehicle-assignments (if vehicle_id provided)
     * - Controller checks: create.accommodation-assignments (if accommodation_id provided)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_id' => 'required|exists:projects,id',
            'role_id' => 'required|exists:roles,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'position' => 'nullable|in:driver,passenger',
            'accommodation_id' => 'nullable|exists:accommodations,id',
        ]);

        $user = $request->user();

        // Check vehicle-assignments permission if vehicle is provided
        if (!empty($validated['vehicle_id'])) {
            if (!$user->hasPermission('create.vehicle-assignments')) {
                abort(403, 'Brak uprawnień do tworzenia przypisań pojazdów.');
            }
        }

        // Check accommodation-assignments permission if accommodation is provided
        if (!empty($validated['accommodation_id'])) {
            if (!$user->hasPermission('create.accommodation-assignments')) {
                abort(403, 'Brak uprawnień do tworzenia przypisań mieszkań.');
            }
        }

        try {
            DB::transaction(function () use ($validated) {
                $employee = Employee::findOrFail($validated['employee_id']);

                // Przypisz do projektu (zawsze)
                $employee->assignments()->create([
                    'project_id' => $validated['project_id'],
                    'role_id' => $validated['role_id'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);

                // Przypisz auto (jeśli wybrano)
                if (!empty($validated['vehicle_id'])) {
                    $employee->vehicleAssignments()->create([
                        'vehicle_id' => $validated['vehicle_id'],
                        'position' => $validated['position'] ?? 'passenger',
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                    ]);
                }

                // Przypisz mieszkanie (jeśli wybrano)
                if (!empty($validated['accommodation_id'])) {
                    $employee->accommodationAssignments()->create([
                        'accommodation_id' => $validated['accommodation_id'],
                        'start_date' => $validated['start_date'],
                        'end_date' => $validated['end_date'],
                    ]);
                }
            });

            return redirect()->route('weekly-overview.index')
                ->with('success', 'Pracownik został przypisany pomyślnie!');
                
        } catch (\Exception $e) {
            Log::error('BulkAssignment::store - Exception', [
                'message' => $e->getMessage(),
                'employee_id' => $validated['employee_id'],
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Nie udało się utworzyć przypisań. Spróbuj ponownie.');
        }
    }
}
