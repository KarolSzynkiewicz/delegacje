<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Sets initial outside_base state for existing employees based on:
     * 1. Last logistics event (DEPARTURE → outside, RETURN → inside)
     * 2. Active project/accommodation assignments
     * 3. Default → inside base
     */
    public function up(): void
    {
        $today = now();

        // Avoid SoftDeletes global scope — deleted_at may not exist yet when this
        // migration runs (soft deletes were added in a later migration).
        foreach (Employee::withoutGlobalScopes()->cursor() as $employee) {
            $shouldBeOutside = false;
            $lastDepartureId = null;
            
            // Check if in transit today
            if (LogisticsEvent::isEmployeeInTransit($employee, $today)) {
                $shouldBeOutside = true;
                
                // Find the departure that put them in transit
                $departure = LogisticsEvent::whereHas('participants', 
                    fn($q) => $q->where('employee_id', $employee->id)
                )
                ->where('type', LogisticsEventType::DEPARTURE)
                ->where('event_date', '<=', $today)
                ->where('end_date', '>', $today)
                ->orderBy('event_date', 'desc')
                ->first();
                
                $lastDepartureId = $departure?->id;
            } else {
                // Check last completed event
                $lastEvent = LogisticsEvent::whereHas('participants', 
                    fn($q) => $q->where('employee_id', $employee->id)
                )
                ->whereIn('type', [LogisticsEventType::DEPARTURE, LogisticsEventType::RETURN])
                ->where('end_date', '<=', $today)
                ->orderBy('end_date', 'desc')
                ->first();
                
                if ($lastEvent) {
                    if ($lastEvent->type === LogisticsEventType::DEPARTURE) {
                        $shouldBeOutside = true;
                        $lastDepartureId = $lastEvent->id;
                    } else {
                        // Return completed → back to base
                        $shouldBeOutside = false;
                    }
                }
            }
            
            // Check active assignments (can override to outside_base = true)
            if (!$shouldBeOutside) {
                $hasActiveProject = $employee->assignments()
                    ->where('start_date', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $today);
                    })
                    ->exists();
                
                if (!$hasActiveProject) {
                    $hasActiveAccommodation = $employee->accommodationAssignments()
                        ->where('start_date', '<=', $today)
                        ->where(function ($q) use ($today) {
                            $q->whereNull('end_date')
                              ->orWhere('end_date', '>=', $today);
                        })
                        ->exists();
                    
                    $shouldBeOutside = $hasActiveAccommodation;
                } else {
                    $shouldBeOutside = true;
                }
            }
            
            // Update employee
            $employee->update([
                'outside_base' => $shouldBeOutside,
                'last_departure_id' => $lastDepartureId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset all to false
        Employee::query()->update([
            'outside_base' => false,
            'last_departure_id' => null,
        ]);
    }
};
