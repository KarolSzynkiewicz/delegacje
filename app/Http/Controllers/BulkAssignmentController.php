<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\Vehicle;
use App\Models\Accommodation;
use App\Enums\VehiclePosition;
use App\Services\ProjectAssignmentService;
use App\Services\VehicleAssignmentService;
use App\Services\AccommodationAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAssignmentController extends Controller
{
    public function __construct(
        protected ProjectAssignmentService $projectAssignmentService,
        protected VehicleAssignmentService $vehicleAssignmentService,
        protected AccommodationAssignmentService $accommodationAssignmentService
    ) {}

    /**
     * Store bulk assignment (project + vehicle + accommodation).
     * 
     * Authorization:
     * - Middleware checks: create.project-assignments (always required)
     * - Controller checks: create.vehicle-assignments (if vehicle_id provided)
     * - Controller checks: create.accommodation-assignments (if accommodation_id provided)
     * 
     * Validation:
     * - Uses the same services as individual controllers
     * - ProjectAssignmentService: validates role, documents, availability, demand
     * - VehicleAssignmentService: validates driver availability, overlap
     * - AccommodationAssignmentService: validates capacity, overlap
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
            'notes' => 'nullable|string',
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
            // Load models
            $employee = Employee::findOrFail($validated['employee_id']);
            $project = Project::findOrFail($validated['project_id']);
            $role = Role::findOrFail($validated['role_id']);
            $startDate = \Carbon\Carbon::parse($validated['start_date']);
            $endDate = \Carbon\Carbon::parse($validated['end_date']);

            DB::transaction(function () use ($employee, $project, $role, $startDate, $endDate, $validated) {
                // 1. Przypisz do projektu (zawsze) - z pełną walidacją
                $this->projectAssignmentService->createAssignment(
                    $project,
                    $employee,
                    $role,
                    $startDate,
                    $endDate,
                    $validated['notes'] ?? null
                );

                // 2. Przypisz auto (jeśli wybrano) - z pełną walidacją
                if (!empty($validated['vehicle_id'])) {
                    $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
                    $position = VehiclePosition::from($validated['position'] ?? 'passenger');
                    
                    $this->vehicleAssignmentService->createAssignment(
                        $employee,
                        $vehicle,
                        $position,
                        $startDate,
                        $endDate,
                        $validated['notes'] ?? null
                    );
                }

                // 3. Przypisz mieszkanie (jeśli wybrano) - z pełną walidacją
                if (!empty($validated['accommodation_id'])) {
                    $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
                    
                    $this->accommodationAssignmentService->createAssignment(
                        $employee,
                        $accommodation,
                        $startDate,
                        $endDate,
                        $validated['notes'] ?? null
                    );
                }
            });

            return redirect()->route('weekly-overview.index', ['start_date' => $startDate->format('Y-m-d')])
                ->with('success', 'Pracownik został przypisany pomyślnie!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors from services - return with user-friendly messages
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Exception $e) {
            Log::error('BulkAssignment::store - Exception', [
                'message' => $e->getMessage(),
                'employee_id' => $validated['employee_id'],
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Nie udało się utworzyć przypisań: ' . $e->getMessage());
        }
    }
}
