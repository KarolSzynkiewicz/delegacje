<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            [
                'name' => 'Uprawnienia spawacza',
                'description' => 'Certyfikat uprawniający do wykonywania prac spawalniczych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Badania lekarskie',
                'description' => 'Aktualne badania lekarskie do celów zawodowych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Uprawnienia elektryka',
                'description' => 'Certyfikat uprawniający do wykonywania prac elektrycznych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Uprawnienia operatora żurawia',
                'description' => 'Licencja na obsługę żurawi i urządzeń dźwigowych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Uprawnienia do pracy na wysokości',
                'description' => 'Certyfikat uprawniający do prac na wysokości',
                'is_periodic' => true,
            ],
            [
                'name' => 'Książeczka sanepid',
                'description' => 'Książeczka zdrowia do celów sanitarno-epidemiologicznych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Szkolenie BHP',
                'description' => 'Aktualne szkolenie z zakresu bezpieczeństwa i higieny pracy',
                'is_periodic' => true,
            ],
            [
                'name' => 'Uprawnienia dekarza',
                'description' => 'Certyfikat uprawniający do wykonywania prac dekarskich',
                'is_periodic' => false,
            ],
            [
                'name' => 'Prawo jazdy kat. B',
                'description' => 'Prawo jazdy kategorii B',
                'is_periodic' => false,
            ],
            [
                'name' => 'Prawo jazdy kat. C',
                'description' => 'Prawo jazdy kategorii C (ciężarowe)',
                'is_periodic' => false,
            ],
            [
                'name' => 'Uprawnienia piaskarza',
                'description' => 'Certyfikat uprawniający do prac piaskarskich',
                'is_periodic' => false,
            ],
            [
                'name' => 'Certyfikat spawania TIG',
                'description' => 'Certyfikat spawania metodą TIG',
                'is_periodic' => true,
            ],
            [
                'name' => 'Certyfikat spawania MIG/MAG',
                'description' => 'Certyfikat spawania metodą MIG/MAG',
                'is_periodic' => true,
            ],
            [
                'name' => 'Uprawnienia do obsługi wózków widłowych',
                'description' => 'Licencja na obsługę wózków widłowych',
                'is_periodic' => true,
            ],
            [
                'name' => 'Szkolenie przeciwpożarowe',
                'description' => 'Aktualne szkolenie z zakresu ochrony przeciwpożarowej',
                'is_periodic' => true,
            ],
        ];

        foreach ($documents as $documentData) {
            Document::firstOrCreate(
                ['name' => $documentData['name']],
                $documentData
            );
        }

        $this->command->info('Utworzono ' . count($documents) . ' wymagań formalnych (dokumentów).');
    }
}
