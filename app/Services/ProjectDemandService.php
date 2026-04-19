<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectDemand;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProjectDemandService
{
    /**
     * Create multiple project demands.
     * Filters out demands with required_count = 0 and validates that at least one demand exists.
     *
     * When saving a window (e.g. one week from the weekly planner) that overlaps a longer existing
     * demand, preserves segments **outside** that window instead of deleting the full range.
     *
     * @param  array  $demands  Array of [role_id => required_count] or [role_id => ['role_id' => int, 'required_count' => int]]
     *
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
                    'role_id' => $roleId,
                    'required_count' => $requiredCount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'notes' => $notes,
                ];
            } else {
                // Zapotrzebowanie do usunięcia (required_count = 0)
                $demandsToDelete[] = $roleId;
            }
        }

        // Check if there is at least one demand to create/update
        if (empty($demandsToProcess) && empty($demandsToDelete)) {
            throw ValidationException::withMessages([
                'demands' => 'Musisz podać ilość większą od 0 dla przynajmniej jednej roli lub ustawić 0 aby usunąć istniejące.',
            ]);
        }

        // Create, update or delete demands in transaction
        DB::beginTransaction();
        try {
            $windowStart = DateRangeService::normalizeDate($startDate);
            $windowEnd = $endDate ? DateRangeService::normalizeDate($endDate) : null;

            // Usuń zapotrzebowania z required_count = 0 (tylko wskazany okres; reszta zakresu zostaje)
            if (! empty($demandsToDelete)) {
                foreach ($demandsToDelete as $roleId) {
                    $this->removeDemandWindowPreservingOutside(
                        $project,
                        (int) $roleId,
                        $windowStart,
                        $windowEnd
                    );
                }
            }

            $createdDemands = [];
            foreach ($demandsToProcess as $demandData) {
                $roleId = $demandData['role_id'];
                $newRow = $this->replaceOverlappingWithSplit(
                    $project,
                    $roleId,
                    DateRangeService::normalizeDate($demandData['start_date']),
                    $demandData['end_date'] ? DateRangeService::normalizeDate($demandData['end_date']) : null,
                    (int) $demandData['required_count'],
                    $demandData['notes'] ?? null
                );
                $createdDemands[] = $newRow;
            }

            DB::commit();

            return $createdDemands;
        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'error' => 'Wystąpił błąd: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Remove demand only within [windowStart, windowEnd], keeping earlier/later segments unchanged.
     */
    private function removeDemandWindowPreservingOutside(
        Project $project,
        int $roleId,
        Carbon $windowStart,
        ?Carbon $windowEnd
    ): void {
        $overlappingDemands = $project->demands()
            ->where('role_id', $roleId)
            ->overlappingWith($windowStart, $windowEnd)
            ->get();

        foreach ($overlappingDemands as $overlappingDemand) {
            $remnants = $this->buildRemnantsOutsideWindow($overlappingDemand, $windowStart, $windowEnd);
            $overlappingDemand->delete();
            foreach ($remnants as $fragment) {
                if ($this->isValidDemandFragment($fragment['start_date'], $fragment['end_date'])) {
                    $project->demands()->create($fragment);
                }
            }
        }
    }

    /**
     * Replace overlapping demands for a role with: remnants (old counts) outside the window + one new row for the window.
     *
     * @return ProjectDemand the newly created centre row
     */
    private function replaceOverlappingWithSplit(
        Project $project,
        int $roleId,
        Carbon $windowStart,
        ?Carbon $windowEnd,
        int $requiredCount,
        ?string $notes
    ): ProjectDemand {
        $newRow = null;

        $overlappingDemands = $project->demands()
            ->where('role_id', $roleId)
            ->overlappingWith($windowStart, $windowEnd)
            ->get();

        foreach ($overlappingDemands as $overlappingDemand) {
            $remnants = $this->buildRemnantsOutsideWindow($overlappingDemand, $windowStart, $windowEnd);
            $overlappingDemand->delete();
            foreach ($remnants as $fragment) {
                if ($this->isValidDemandFragment($fragment['start_date'], $fragment['end_date'])) {
                    $project->demands()->create($fragment);
                }
            }
        }

        if ($this->isValidDemandFragment($windowStart, $windowEnd)) {
            $newRow = $project->demands()->create([
                'role_id' => $roleId,
                'required_count' => $requiredCount,
                'start_date' => $windowStart,
                'end_date' => $windowEnd,
                'notes' => $notes,
            ]);
        }

        if ($newRow === null) {
            throw ValidationException::withMessages([
                'demands' => 'Po podziale zakresów nie udało się utworzyć zapotrzebowania — nieprawidłowy okres dat.',
            ]);
        }

        return $newRow;
    }

    /**
     * Parts of an existing demand that lie strictly outside [windowStart, windowEnd], same required_count/notes.
     *
     * @return array<int, array{project_id?: int, role_id: int, required_count: int, start_date: Carbon, end_date: Carbon|null, notes: string|null}>
     */
    private function buildRemnantsOutsideWindow(
        ProjectDemand $old,
        Carbon $windowStart,
        ?Carbon $windowEnd
    ): array {
        $oldStart = DateRangeService::normalizeDate($old->start_date);
        $oldEnd = $old->end_date ? DateRangeService::normalizeDate($old->end_date) : null;
        $wStart = DateRangeService::normalizeDate($windowStart);
        $wEnd = $windowEnd ? DateRangeService::normalizeDate($windowEnd) : null;

        $common = [
            'role_id' => (int) $old->role_id,
            'required_count' => (int) $old->required_count,
            'notes' => $old->notes,
        ];

        $out = [];

        // Lewy fragment: przed oknem formularza
        if ($oldStart->lt($wStart)) {
            $leftEndCap = $wStart->copy()->subDay();
            $leftEnd = $oldEnd === null ? $leftEndCap : $leftEndCap->min($oldEnd);
            if ($oldStart->lte($leftEnd)) {
                $out[] = array_merge($common, [
                    'start_date' => $oldStart->copy(),
                    'end_date' => $leftEnd->copy(),
                ]);
            }
        }

        // Prawy fragment: po oknie (tylko gdy okno ma skończony koniec; okno [S, ∞) zjada cały „ogon” od S w górę)
        if ($wEnd !== null) {
            $rightStart = $wEnd->copy()->addDay();
            if ($oldEnd === null) {
                $out[] = array_merge($common, [
                    'start_date' => $rightStart->copy(),
                    'end_date' => null,
                ]);
            } elseif ($rightStart->lte($oldEnd)) {
                $out[] = array_merge($common, [
                    'start_date' => $rightStart->copy(),
                    'end_date' => $oldEnd->copy(),
                ]);
            }
        }

        return $out;
    }

    private function isValidDemandFragment(Carbon $start, ?Carbon $end): bool
    {
        if ($end === null) {
            return true;
        }

        return DateRangeService::isValidRange($start, $end);
    }
}
