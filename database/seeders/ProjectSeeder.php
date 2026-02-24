<?php

namespace Database\Seeders;

use App\Enums\ProjectType;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all();
        
        if ($locations->isEmpty()) {
            $this->command->warn('Brak lokalizacji w bazie. Uruchom najpierw LocationSeeder.');
            return;
        }

        // Daty: od pierwszego dnia aktualnego miesiąca do ostatniego dnia następnego miesiąca
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonth()->endOfMonth();

        // 4 projekty z kwotą zakontraktowaną po 100k
        $contractProjects = [
            [
                'name' => 'Budowa kontenerowca MSC-2024',
                'description' => 'Budowa nowoczesnego kontenerowca o pojemności 15,000 TEU. Projekt obejmuje montaż kadłuba, instalację systemów pokładowych oraz wyposażenie.',
                'client_name' => 'MSC Mediterranean Shipping Company',
            ],
            [
                'name' => 'Remont statku pasażerskiego Baltic Star',
                'description' => 'Kompleksowy remont i modernizacja statku pasażerskiego. Wymiana systemów nawigacyjnych, remont kabin oraz modernizacja restauracji.',
                'client_name' => 'Polferries',
            ],
            [
                'name' => 'Modernizacja platformy wiertniczej',
                'description' => 'Modernizacja systemów bezpieczeństwa i wydajności platformy wiertniczej. Wymiana urządzeń kontrolnych i systemów alarmowych.',
                'client_name' => 'PKN Orlen',
            ],
            [
                'name' => 'Budowa jednostki ratowniczej SAR-2024',
                'description' => 'Budowa specjalistycznej jednostki ratowniczej dla służb morskich. Wyposażenie w zaawansowane systemy ratownicze i nawigacyjne.',
                'client_name' => 'Morska Służba Poszukiwania i Ratownictwa',
            ],
        ];

        // 4 projekty ze stawką godzinową (30 EUR)
        $hourlyProjects = [
            [
                'name' => 'Remont kutra rybackiego B-123',
                'description' => 'Remont generalny kutra rybackiego. Wymiana silnika, naprawa kadłuba oraz modernizacja wyposażenia.',
                'client_name' => 'Przedsiębiorstwo Rybackie "Bałtyk"',
            ],
            [
                'name' => 'Remont i modernizacja promu morskiego',
                'description' => 'Kompleksowa modernizacja promu pasażersko-samochodowego. Wymiana silników, remont pokładów, modernizacja systemów bezpieczeństwa.',
                'client_name' => 'Stena Line',
            ],
            [
                'name' => 'Remont jachtu żaglowego klasy regatowej',
                'description' => 'Remont i modernizacja jachtu żaglowego przygotowywanego do regat oceanicznych. Wymiana olinowania, remont kadłuba, optymalizacja wyposażenia.',
                'client_name' => 'Yacht Club Gdańsk',
            ],
            [
                'name' => 'Budowa jachtu motorowego klasy premium',
                'description' => 'Budowa luksusowego jachtu motorowego o długości 45 metrów. Projekt indywidualny dla prywatnego klienta.',
                'client_name' => 'Private Client',
            ],
        ];

        $created = 0;

        // Tworzenie projektów z kwotą zakontraktowaną (4 projekty po 100k)
        foreach ($contractProjects as $projectData) {
            $project = Project::firstOrCreate(
                ['name' => $projectData['name']],
                [
                    'location_id' => $locations->random()->id,
                    'name' => $projectData['name'],
                    'description' => $projectData['description'],
                    'status' => 'active',
                    'type' => ProjectType::CONTRACT,
                    'client_name' => $projectData['client_name'],
                    'budget' => 100000.00,
                    'contract_amount' => 100000.00,
                    'currency' => 'PLN',
                    'hourly_rate' => null,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
            if ($project->wasRecentlyCreated) {
                $created++;
            }
        }

        // Tworzenie projektów ze stawką godzinową (4 projekty, stawka 30 EUR)
        foreach ($hourlyProjects as $projectData) {
            $project = Project::firstOrCreate(
                ['name' => $projectData['name']],
                [
                    'location_id' => $locations->random()->id,
                    'name' => $projectData['name'],
                    'description' => $projectData['description'],
                    'status' => 'active',
                    'type' => ProjectType::HOURLY,
                    'client_name' => $projectData['client_name'],
                    'budget' => null,
                    'contract_amount' => null,
                    'currency' => 'EUR',
                    'hourly_rate' => 30.00,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]
            );
            if ($project->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info('Sprawdzono 8 projektów. Utworzono ' . $created . ' nowych.');
    }
}

