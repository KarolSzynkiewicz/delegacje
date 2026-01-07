<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Project;
use Illuminate\Database\Seeder;

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

        // Tylko 5 projektów dla demonstracji
        $projects = [
            [
                'location_id' => $locations->random()->id,
                'name' => 'Budowa kontenerowca MSC-2024',
                'description' => 'Budowa nowoczesnego kontenerowca o pojemności 15,000 TEU. Projekt obejmuje montaż kadłuba, instalację systemów pokładowych oraz wyposażenie.',
                'status' => 'active',
                'client_name' => 'MSC Mediterranean Shipping Company',
                'budget' => 85000000.00,
            ],
            [
                'location_id' => $locations->random()->id,
                'name' => 'Remont statku pasażerskiego Baltic Star',
                'description' => 'Kompleksowy remont i modernizacja statku pasażerskiego. Wymiana systemów nawigacyjnych, remont kabin oraz modernizacja restauracji.',
                'status' => 'active',
                'client_name' => 'Polferries',
                'budget' => 12000000.00,
            ],
            [
                'location_id' => $locations->random()->id,
                'name' => 'Budowa jachtu motorowego klasy premium',
                'description' => 'Budowa luksusowego jachtu motorowego o długości 45 metrów. Projekt indywidualny dla prywatnego klienta.',
                'status' => 'active',
                'client_name' => 'Private Client',
                'budget' => 25000000.00,
            ],
            [
                'location_id' => $locations->random()->id,
                'name' => 'Modernizacja platformy wiertniczej',
                'description' => 'Modernizacja systemów bezpieczeństwa i wydajności platformy wiertniczej. Wymiana urządzeń kontrolnych i systemów alarmowych.',
                'status' => 'active',
                'client_name' => 'PKN Orlen',
                'budget' => 45000000.00,
            ],
            [
                'location_id' => $locations->random()->id,
                'name' => 'Remont kutra rybackiego B-123',
                'description' => 'Remont generalny kutra rybackiego. Wymiana silnika, naprawa kadłuba oraz modernizacja wyposażenia.',
                'status' => 'active',
                'client_name' => 'Przedsiębiorstwo Rybackie "Bałtyk"',
                'budget' => 850000.00,
            ],
        ];

        $created = 0;
        foreach ($projects as $projectData) {
            $project = Project::firstOrCreate(
                ['name' => $projectData['name']],
                $projectData
            );
            if ($project->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info('Sprawdzono ' . count($projects) . ' projektów. Utworzono ' . $created . ' nowych.');
    }
}

