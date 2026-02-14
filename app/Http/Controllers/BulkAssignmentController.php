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
            
            // Project dates (required)
            'project_start_date' => 'required|date',
            'project_end_date' => 'required|date|after_or_equal:project_start_date',
            'project_id' => 'required|exists:projects,id',
            'role_id' => 'required|exists:roles,id',
            
            // Vehicle dates (REQUIRED - wszystko obowiązkowe dla spójności)
            'vehicle_id' => 'required|exists:vehicles,id',
            'position' => 'required|in:driver,passenger',
            'vehicle_start_date' => 'required|date',
            'vehicle_end_date' => 'required|date|after_or_equal:vehicle_start_date',
            
            // Accommodation dates (REQUIRED - wszystko obowiązkowe dla spójności)
            'accommodation_id' => 'required|exists:accommodations,id',
            'accommodation_start_date' => 'required|date',
            'accommodation_end_date' => 'required|date|after_or_equal:accommodation_start_date',
            
            'logistics_event_id' => 'nullable|exists:logistics_events,id',
        ]);

        // Wszystkie permissions są sprawdzane przez middleware (bulk-assignments wymaga wszystkich 3)
        
        try {
            // Load models
            $employee = Employee::findOrFail($validated['employee_id']);
            $project = Project::findOrFail($validated['project_id']);
            $role = Role::findOrFail($validated['role_id']);
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            $accommodation = Accommodation::findOrFail($validated['accommodation_id']);
            
            // Parse dates
            $projectStartDate = \Carbon\Carbon::parse($validated['project_start_date']);
            $projectEndDate = \Carbon\Carbon::parse($validated['project_end_date']);
            $vehicleStartDate = \Carbon\Carbon::parse($validated['vehicle_start_date']);
            $vehicleEndDate = \Carbon\Carbon::parse($validated['vehicle_end_date']);
            $accommodationStartDate = \Carbon\Carbon::parse($validated['accommodation_start_date']);
            $accommodationEndDate = \Carbon\Carbon::parse($validated['accommodation_end_date']);
            
            $position = VehiclePosition::from($validated['position']);
            $logisticsEventId = $validated['logistics_event_id'] ?? null;

            DB::transaction(function () use ($employee, $project, $role, $vehicle, $accommodation, $position, $projectStartDate, $projectEndDate, $vehicleStartDate, $vehicleEndDate, $accommodationStartDate, $accommodationEndDate, $logisticsEventId) {
                // 1. Przypisz do projektu - z pełną walidacją
                $this->projectAssignmentService->createAssignment(
                    $project,
                    $employee,
                    $role,
                    $projectStartDate,
                    $projectEndDate,
                    null,
                    $logisticsEventId
                );

                // 2. Przypisz auto - z pełną walidacją
                $this->vehicleAssignmentService->createAssignment(
                    $employee,
                    $vehicle,
                    $position,
                    $vehicleStartDate,
                    $vehicleEndDate,
                    null,
                    $logisticsEventId
                );

                // 3. Przypisz mieszkanie - z pełną walidacją
                $this->accommodationAssignmentService->createAssignment(
                    $employee,
                    $accommodation,
                    $accommodationStartDate,
                    $accommodationEndDate,
                    null,
                    $logisticsEventId
                );
                
                // 4. Zaktualizuj status wyjazdu (jeśli istnieje logistics_event_id)
                if ($logisticsEventId) {
                    $logisticsEvent = \App\Models\LogisticsEvent::find($logisticsEventId);
                    if ($logisticsEvent) {
                        $logisticsEvent->updateCompletionStatus();
                    }
                }
            });

            return redirect()->route('weekly-overview.index', ['start_date' => $projectStartDate->format('Y-m-d')])
                ->with('success', 'Pracownik został kompleksowo przypisany (projekt + pojazd + zakwaterowanie)!');
                
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
