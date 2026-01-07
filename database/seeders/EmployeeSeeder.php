<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Role;
use App\Models\EmployeeDocument;
use App\Models\Rotation;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pobierz wszystkie role
        $spawaczRole = Role::where('name', 'Spawacz')->first();
        $dekarzRole = Role::where('name', 'Dekarz')->first();
        $elektrykRole = Role::where('name', 'Elektryk')->first();
        $operatorRole = Role::where('name', 'Operator')->first();
        $piaskarzRole = Role::where('name', 'piaskarz')->first();

        // Lista polskich imion i nazwisk
        $firstNames = [
            'Jan', 'Piotr', 'Andrzej', 'Krzysztof', 'Tomasz', 'Marcin', 'Michał', 'Paweł', 'Jakub', 'Maciej',
            'Adam', 'Łukasz', 'Marek', 'Grzegorz', 'Dariusz', 'Wojciech', 'Rafał', 'Robert', 'Kamil', 'Sebastian',
            'Anna', 'Maria', 'Katarzyna', 'Magdalena', 'Agnieszka', 'Barbara', 'Ewa', 'Joanna', 'Natalia', 'Aleksandra',
            'Karolina', 'Monika', 'Justyna', 'Paulina', 'Patrycja', 'Sylwia', 'Dominika', 'Weronika', 'Martyna', 'Julia'
        ];

        $lastNames = [
            'Kowalski', 'Nowak', 'Wiśniewski', 'Wójcik', 'Kowalczyk', 'Kamiński', 'Lewandowski', 'Zieliński', 'Szymański', 'Woźniak',
            'Dąbrowski', 'Kozłowski', 'Jankowski', 'Mazur', 'Kwiatkowski', 'Krawczyk', 'Piotrowski', 'Grabowski', 'Nowakowski', 'Pawłowski',
            'Michalski', 'Nowicki', 'Adamczyk', 'Dudek', 'Zając', 'Wieczorek', 'Jabłoński', 'Król', 'Majewski', 'Olszewski',
            'Jaworski', 'Wróbel', 'Malinowski', 'Pawlak', 'Witkowski', 'Walczak', 'Stepień', 'Górski', 'Rutkowski', 'Michalak'
        ];

        $employees = [];
        $counter = 1;

        // Generuj około 80 pracowników
        for ($i = 0; $i < 80; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . $counter . '@example.com');
            
            // Sprawdź czy email już istnieje
            while (Employee::where('email', $email)->exists()) {
                $counter++;
                $email = strtolower($firstName . '.' . $lastName . $counter . '@example.com');
            }

            $phone = '+48 ' . rand(500, 999) . ' ' . rand(100, 999) . ' ' . rand(100, 999);

            $employee = Employee::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'notes' => rand(0, 1) ? 'Pracownik doświadczony' : null,
            ]);

            // Przypisz losowe role (1-3 role na pracownika)
            $availableRoles = [$spawaczRole, $dekarzRole, $elektrykRole, $operatorRole, $piaskarzRole];
            $numRoles = rand(1, 3);
            $selectedRoles = array_rand($availableRoles, $numRoles);
            
            if (!is_array($selectedRoles)) {
                $selectedRoles = [$selectedRoles];
            }

            foreach ($selectedRoles as $roleIndex) {
                if ($availableRoles[$roleIndex]) {
                    $employee->roles()->attach($availableRoles[$roleIndex]->id);
                }
            }

            // Utwórz rotację dla pracownika (następne 3 miesiące)
            $rotationStart = Carbon::now()->startOfWeek()->subWeeks(1);
            $rotationEnd = Carbon::now()->addMonths(3);
            
            Rotation::firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'start_date' => $rotationStart->format('Y-m-d'),
                    'end_date' => $rotationEnd->format('Y-m-d'),
                ],
                [
                    'status' => 'active',
                    'notes' => 'Rotacja automatyczna z seedera',
                ]
            );

            $employees[] = $employee;
            $counter++;
        }

        // Pobierz dostępne dokumenty
        $documents = \App\Models\Document::all();
        
        if ($documents->count() > 0) {
            // Dodaj przykładowe dokumenty dla kilku pracowników
            foreach (array_slice($employees, 0, 15) as $employee) {
                $numDocuments = rand(1, 3);
                $selectedDocs = $documents->random(min($numDocuments, $documents->count()));
                
                if (!is_iterable($selectedDocs)) {
                    $selectedDocs = [$selectedDocs];
                }
                
                foreach ($selectedDocs as $document) {
                    $isPeriodic = rand(0, 1);
                    EmployeeDocument::create([
                        'employee_id' => $employee->id,
                        'document_id' => $document->id,
                        'valid_from' => now()->subYears(rand(1, 3))->format('Y-m-d'),
                        'valid_to' => $isPeriodic ? now()->addYears(rand(1, 3))->format('Y-m-d') : null,
                        'kind' => $isPeriodic ? 'okresowy' : 'bezokresowy',
                        'file_path' => null,
                    ]);
                }
            }
        }
    }
}
