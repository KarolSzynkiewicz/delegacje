<?php

namespace App\Services;

use App\Models\EmployeeDocument;
use App\Models\Vehicle;
use App\Models\Accommodation;
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
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
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
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
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
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
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
     * @param Carbon $monthStart
     * @param Carbon $monthEnd
     * @return Collection<Accommodation>
     */
    public function getExpiringLeases(Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return Accommodation::where('type', 'wynajmowany')
            ->whereNotNull('lease_end_date')
            ->whereBetween('lease_end_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->with('location')
            ->orderBy('lease_end_date')
            ->get();
    }
}
