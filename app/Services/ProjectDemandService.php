<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectDemandService
{
    /**
     * Create multiple project demands.
     * Filters out demands with required_count = 0 and validates that at least one demand exists.
     *
     * @param Project $project
     * @param Carbon $startDate
     * @param Carbon|null $endDate
     * @param string|null $notes
     * @param array $demands Array of [role_id => required_count] or [role_id => ['role_id' => int, 'required_count' => int]]
     * @return array
     * @throws ValidationException
     */
    public function createDemands(
        Project $project,
        Carbon $startDate,
        ?Carbon $endDate = null,
        ?string $notes = null,
        array $demands = []
    ): array {
        // Przetwórz wszystkie role (również te z required_count = 0, aby je usunąć)
        $demandsToProcess = [];
        $demandsToDelete = [];
        
        foreach ($demands as $roleId => $demandData) {
            // Support both formats: [role_id => count] or [role_id => ['role_id' => int, 'required_count' => int]]
            if (is_array($demandData)) {
                $roleId = (int) ($demandData['role_id'] ?? $roleId);
                $requiredCount = (int) ($demandData['required_count'] ?? 0);
            } else {
                $requiredCount = (int) $demandData;
            }
            
            if ($requiredCount > 0) {
                // Zapotrzebowanie do utworzenia/aktualizacji
                $demandsToProcess[] = [
                    "role_id" => $roleId,
                    "required_count" => $requiredCount,
                    "start_date" => $startDate,
                    "end_date" => $endDate,
                    "notes" => $notes,
                ];
            } else {
                // Zapotrzebowanie do usunięcia (required_count = 0)
                $demandsToDelete[] = $roleId;
            }
        }

        // Check if there is at least one demand to create/update
        if (empty($demandsToProcess) && empty($demandsToDelete)) {
            throw ValidationException::withMessages([
                "demands" => "Musisz podać ilość większą od 0 dla przynajmniej jednej roli lub ustawić 0 aby usunąć istniejące."
            ]);
        }

        // Create, update or delete demands in transaction
        DB::beginTransaction();
        try {
            // Usuń zapotrzebowania z required_count = 0
            if (!empty($demandsToDelete)) {
                $project->demands()
                    ->whereIn('role_id', $demandsToDelete)
                    ->where('start_date', '<=', $endDate ?? $startDate)
                    ->where(function ($q) use ($startDate) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', $startDate);
                    })
                    ->delete();
            }

            $createdDemands = [];
            foreach ($demandsToProcess as $demandData) {
                // Znajdź wszystkie istniejące zapotrzebowania dla tej roli, które się nakładają z nowym okresem
                // Użyj scopeOverlappingWith z HasDateRange trait
                $overlappingDemands = $project->demands()
                    ->where('role_id', $demandData['role_id'])
                    ->overlappingWith($demandData['start_date'], $demandData['end_date'])
                    ->get();

                // Usuń wszystkie nakładające się zapotrzebowania (nadpisujemy je nowym)
                foreach ($overlappingDemands as $overlappingDemand) {
                    $overlappingDemand->delete();
                }

                // Utwórz nowe zapotrzebowanie (nadpisuje wszystkie poprzednie dla tej roli w tym okresie)
                $createdDemands[] = $project->demands()->create($demandData);
            }

            DB::commit();

            return $createdDemands;
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                "error" => "Wystąpił błąd: " . $e->getMessage()
            ]);
        }
    }
}
