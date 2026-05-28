<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\EmployeeDocument;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for retrieving expiring documents, insurance, and leases for the current month.
 *
 * Returns:
 * - Employee documents expiring this month
 * - Vehicle inspections expiring this month
 * - Accommodation leases expiring this month (only rented accommodations)
 */
class ExpiringDocumentsService
{
    /**
     * Get all expiring items for the current month.
     *
     * @return array{
     *     documents: Collection<EmployeeDocument>,
     *     vehicle_inspections: Collection<Vehicle>,
     *     vehicle_insurance: Collection<Vehicle>,
     *     accommodations: Collection<Accommodation>
     * }
     */
    public function getExpiringThisMonth(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        return [
            'documents' => $this->getExpiringDocuments($monthStart, $monthEnd),
            'vehicle_inspections' => $this->getExpiringVehicleInspections($monthStart, $monthEnd),
            'vehicle_insurance' => $this->getExpiringVehicleInsurance($monthStart, $monthEnd),
            'accommodations' => $this->getExpiringLeases($monthStart, $monthEnd),
        ];
    }

    /**
     * Get employee documents expiring in the given month range.
     * Only returns documents with valid_to date (not bezokresowy).
     *
     * @return Collection<EmployeeDocument>
     */
    public function getExpiringDocuments(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return EmployeeDocument::where('kind', 'okresowy')
            ->whereNotNull('valid_to')
            ->whereBetween('valid_to', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->with(['employee', 'document'])
            ->orderBy('valid_to')
            ->get();
    }

    /**
     * Get vehicles with inspections expiring in the given month range.
     *
     * @return Collection<Vehicle>
     */
    public function getExpiringVehicleInspections(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return Vehicle::whereNotNull('inspection_valid_to')
            ->whereBetween('inspection_valid_to', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->orderBy('inspection_valid_to')
            ->get();
    }

    /**
     * Get vehicles with insurance expiring in the given month range.
     *
     * @return Collection<Vehicle>
     */
    public function getExpiringVehicleInsurance(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return Vehicle::whereNotNull('insurance_valid_to')
            ->whereBetween('insurance_valid_to', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->orderBy('insurance_valid_to')
            ->get();
    }

    /**
     * Get rented accommodations with leases expiring in the given month range.
     * Only returns accommodations with type='wynajmowany'.
     *
     * @return Collection<Accommodation>
     */
    public function getExpiringLeases(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return Accommodation::whereHas('leases', function ($q) use ($monthStart, $monthEnd) {
            $q->where('type', 'wynajmowany')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);
        })->with(['activeLease'])->get();
    }

    /**
     * Get expiring documents for a specific employee within the next N days.
     * Also includes expired required documents (within last N days).
     *
     * When the `employeeDocuments` relation is already loaded on the model,
     * filters in memory to avoid N+1 queries in loops.
     *
     * @param  int  $days  Number of days to look ahead (default: 30)
     * @return Collection<EmployeeDocument>
     */
    public function getExpiringDocumentsForEmployee(\App\Models\Employee $employee, int $days = 30): Collection
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays($days);
        $endDate = $now->copy()->addDays($days);

        if ($employee->relationLoaded('employeeDocuments')) {
            $docs = $employee->employeeDocuments;

            $expiring = $docs->filter(fn ($doc) => $doc->kind === 'okresowy'
                && $doc->valid_to !== null
                && $doc->valid_to->gte($now)
                && $doc->valid_to->lte($endDate)
            );

            $expiredRequired = $docs->filter(fn ($doc) => $doc->kind === 'okresowy'
                && $doc->valid_to !== null
                && $doc->valid_to->lt($now)
                && $doc->valid_to->gte($startDate)
                && $doc->document
                && $doc->document->is_required
            );

            return $expiring->merge($expiredRequired)->sortBy('valid_to')->values();
        }

        // DB fallback (when relation is not pre-loaded)
        $expiring = EmployeeDocument::where('employee_id', $employee->id)
            ->where('kind', 'okresowy')
            ->whereNotNull('valid_to')
            ->where('valid_to', '>=', $now->format('Y-m-d'))
            ->where('valid_to', '<=', $endDate->format('Y-m-d'))
            ->with('document')
            ->get();

        $expiredRequired = EmployeeDocument::where('employee_id', $employee->id)
            ->where('kind', 'okresowy')
            ->whereNotNull('valid_to')
            ->where('valid_to', '<', $now->format('Y-m-d'))
            ->where('valid_to', '>=', $startDate->format('Y-m-d'))
            ->with('document')
            ->get()
            ->filter(fn ($doc) => $doc->document && $doc->document->is_required);

        return $expiring->merge($expiredRequired)->sortBy('valid_to')->values();
    }
}
