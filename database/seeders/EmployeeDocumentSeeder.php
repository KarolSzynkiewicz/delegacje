<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Document;
use App\Models\EmployeeDocument;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EmployeeDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Tworzy dokumenty pracowników na podstawie istniejących pracowników i dokumentów.
     * Dla każdego pracownika tworzy 1-4 losowe dokumenty z odpowiednimi datami ważności.
     */
    public function run(): void
    {
        $employees = Employee::all();
        $documents = Document::all();

        if ($employees->isEmpty()) {
            $this->command->warn('Brak pracowników w bazie. Uruchom najpierw EmployeeSeeder.');
            return;
        }

        if ($documents->isEmpty()) {
            $this->command->warn('Brak dokumentów w bazie. Uruchom najpierw DocumentSeeder.');
            return;
        }

        $createdCount = 0;
        $employeesArray = $employees->toArray();
        $totalEmployees = count($employeesArray);
        
        // 90% pracowników dostanie wszystkie dokumenty, 10% dostanie większość (80-90%)
        $employeesWithAllDocs = (int)($totalEmployees * 0.9);
        $employeesWithMostDocs = $totalEmployees - $employeesWithAllDocs;

        foreach ($employees as $index => $employee) {
            // Określ ile dokumentów przypisać
            $shouldHaveAllDocs = $index < $employeesWithAllDocs;
            
            if ($shouldHaveAllDocs) {
                // Wszystkie dokumenty
                $selectedDocs = $documents;
            } else {
                // 80-90% dokumentów (losowo)
                $percentage = rand(80, 90) / 100;
                $numDocs = max(1, (int)($documents->count() * $percentage));
                $selectedDocs = $documents->random($numDocs);
            }

            if (!is_iterable($selectedDocs)) {
                $selectedDocs = [$selectedDocs];
            }

            // 20% pracowników będzie miało niektóre dokumenty kończące się wkrótce
            $hasExpiringDocs = rand(1, 100) <= 20;

            foreach ($selectedDocs as $document) {
                // Sprawdź czy dokument już istnieje dla tego pracownika
                $existing = EmployeeDocument::where('employee_id', $employee->id)
                    ->where('document_id', $document->id)
                    ->first();

                if ($existing) {
                    continue; // Pomiń jeśli już istnieje
                }

                // Określ czy dokument jest okresowy
                $isPeriodic = $document->is_periodic;
                
                // Dla dokumentów okresowych: ważność 1-3 lata w przyszłość
                // Dla bezokresowych: tylko data rozpoczęcia
                $validFrom = Carbon::now()->subYears(rand(0, 2))->subMonths(rand(0, 11));
                
                if ($isPeriodic) {
                    // Jeśli pracownik ma dokumenty kończące się wkrótce i to jest jeden z pierwszych dokumentów
                    if ($hasExpiringDocs && rand(1, 100) <= 30) {
                        // Dokument kończy się wkrótce (1-30 dni)
                        $validTo = Carbon::now()->addDays(rand(1, 30));
                    } else {
                        // Normalna ważność: 1-3 lata w przyszłość
                        $validTo = $validFrom->copy()->addYears(rand(1, 3))->addMonths(rand(0, 11));
                    }
                } else {
                    $validTo = null;
                }

                EmployeeDocument::create([
                    'employee_id' => $employee->id,
                    'document_id' => $document->id,
                    'valid_from' => $validFrom->format('Y-m-d'),
                    'valid_to' => $validTo ? $validTo->format('Y-m-d') : null,
                    'kind' => $isPeriodic ? 'okresowy' : 'bezokresowy',
                    'file_path' => null,
                ]);

                $createdCount++;
            }
        }

        $this->command->info("Utworzono {$createdCount} dokumentów pracowników.");
    }
}
