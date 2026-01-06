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
        // Filter only roles with required_count > 0
        $demandsToCreate = [];
        foreach ($validated["demands"] as $roleId => $demandData) {
            // Check if data is correct
            if (!isset($demandData["role_id"]) || !isset($demandData["required_count"])) {
                continue;
            }
            
            $requiredCount = (int) $demandData["required_count"];
            if ($requiredCount > 0) {
                $demandsToCreate[] = [
                    "role_id" => (int) $demandData["role_id"],
                    "required_count" => $requiredCount,
                    "date_from" => $validated["date_from"],
                    "date_to" => $validated["date_to"] ?? null,
                    "notes" => $validated["notes"] ?? null,
                ];
            }
        }

        // Check if there is at least one demand
        if (empty($demandsToCreate)) {
            throw ValidationException::withMessages([
                "demands" => "Musisz podać ilość większą od 0 dla przynajmniej jednej roli."
            ]);
        }

        // Create demands in transaction
        DB::beginTransaction();
        try {
            $createdDemands = [];
            foreach ($demandsToCreate as $demandData) {
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
