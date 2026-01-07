<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProjectDemandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Tworzy zapotrzebowania dla każdego projektu:
     * - 5-10 zapotrzebowań na różne role
     * - Na najbliższe 3 tygodnie
     */
    public function run(): void
    {
        $projects = Project::all();
        $roles = Role::all();

        if ($projects->isEmpty()) {
            $this->command->warn('Brak projektów w bazie. Uruchom najpierw ProjectSeeder.');
            return;
        }

        if ($roles->isEmpty()) {
            $this->command->warn('Brak ról w bazie. Uruchom najpierw RoleSeeder.');
            return;
        }

        // Okres: najbliższe 3 tygodnie
        $dateFrom = Carbon::now()->startOfWeek();
        $dateTo = Carbon::now()->startOfWeek()->addWeeks(3)->subDay();

        // Usuń istniejące zapotrzebowania z seedera (zachowaj te utworzone ręcznie)
        ProjectDemand::where('notes', 'Zapotrzebowanie automatyczne z seedera')->delete();

        $createdCount = 0;

        foreach ($projects as $project) {
            // Cel: 5-10 osób łącznie na projekt
            $totalRequired = rand(5, 10);
            
            // Wybierz różne role (2-4 role na projekt)
            $numRoles = min(rand(2, 4), $roles->count());
            $selectedRoles = $roles->random($numRoles);
            
            if (!is_iterable($selectedRoles)) {
                $selectedRoles = [$selectedRoles];
            }

            // Rozdziel wymaganą liczbę osób między role
            $remaining = $totalRequired;
            $roleCounts = [];
            
            foreach ($selectedRoles as $index => $role) {
                if ($index === count($selectedRoles) - 1) {
                    // Ostatnia rola dostaje resztę
                    $roleCounts[$role->id] = $remaining;
                } else {
                    // Każda rola dostaje 1-3 osoby (ale nie więcej niż pozostało)
                    $count = min(rand(1, 3), $remaining);
                    $roleCounts[$role->id] = $count;
                    $remaining -= $count;
                }
            }

            foreach ($selectedRoles as $role) {
                $requiredCount = $roleCounts[$role->id] ?? 1;

                ProjectDemand::create([
                    'project_id' => $project->id,
                    'role_id' => $role->id,
                    'required_count' => $requiredCount,
                    'date_from' => $dateFrom->format('Y-m-d'),
                    'date_to' => $dateTo->format('Y-m-d'),
                    'notes' => 'Zapotrzebowanie automatyczne z seedera',
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Utworzono {$createdCount} zapotrzebowań dla projektów.");
    }
}
