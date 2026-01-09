<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDemand;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectDemandService
{
    /**
     * Create multiple project demands from validated data.
     * Filters out demands with required_count = 0 and validates that at least one demand exists.
     *
     * @throws ValidationException
     */
    public function createDemands(Project $project, array $validated): array
    {
        // Przetwórz wszystkie role (również te z required_count = 0, aby je usunąć)
        $demandsToProcess = [];
        $demandsToDelete = [];
        
        foreach ($validated["demands"] as $roleId => $demandData) {
            // Check if data is correct
            if (!isset($demandData["role_id"]) || !isset($demandData["required_count"])) {
                continue;
            }
            
            $requiredCount = (int) $demandData["required_count"];
            
            if ($requiredCount > 0) {
                // Zapotrzebowanie do utworzenia/aktualizacji
                $demandsToProcess[] = [
                    "role_id" => (int) $demandData["role_id"],
                    "required_count" => $requiredCount,
                    "date_from" => $validated["date_from"],
                    "date_to" => $validated["date_to"] ?? null,
                    "notes" => $validated["notes"] ?? null,
                ];
            } else {
                // Zapotrzebowanie do usunięcia (required_count = 0)
                $demandsToDelete[] = (int) $demandData["role_id"];
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
                    ->where('date_from', '<=', $validated['date_to'] ?? $validated['date_from'])
                    ->where(function ($q) use ($validated) {
                        $q->whereNull('date_to')
                          ->orWhere('date_to', '>=', $validated['date_from']);
                    })
                    ->delete();
            }

            $createdDemands = [];
            foreach ($demandsToProcess as $demandData) {
                // Sprawdź czy zapotrzebowanie już istnieje dla tej roli w tym okresie
                // Użyj dokładnego dopasowania dat, aby uniknąć duplikatów
                $existingDemand = $project->demands()
                    ->where('role_id', $demandData['role_id'])
                    ->where('date_from', $demandData['date_from'])
                    ->where(function ($q) use ($demandData) {
                        if ($demandData['date_to']) {
                            $q->where('date_to', $demandData['date_to']);
                        } else {
                            $q->whereNull('date_to');
                        }
                    })
                    ->first();

                if ($existingDemand) {
                    // Aktualizuj istniejące zapotrzebowanie
                    \Log::info('Updating existing demand via createDemands', [
                        'existing_demand_id' => $existingDemand->id,
                        'old_count' => $existingDemand->required_count,
                        'new_count' => $demandData['required_count'],
                        'demand_data' => $demandData
                    ]);
                    
                    $updated = $existingDemand->update([
                        'required_count' => $demandData['required_count'],
                        'date_from' => $demandData['date_from'],
                        'date_to' => $demandData['date_to'],
                        'notes' => $demandData['notes'],
                    ]);
                    
                    \Log::info('Update result', [
                        'updated' => $updated,
                        'demand_after' => $existingDemand->fresh()->toArray()
                    ]);
                    
                    $createdDemands[] = $existingDemand;
                } else {
                    // Utwórz nowe zapotrzebowanie
                    \Log::info('Creating new demand', ['demand_data' => $demandData]);
                    $createdDemands[] = $project->demands()->create($demandData);
                }
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
