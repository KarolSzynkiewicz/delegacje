<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Accommodation;

class AccommodationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Accommodation::create([
            'name' => 'Mieszkanie 1',
            'address' => 'ul. Portowa 10',
            'city' => 'Gda\u0144sk',
            'postal_code' => '80-001',
            'capacity' => 4,
            'description' => 'Mieszkanie przy porcie, blisko stoczni'
        ]);

        Accommodation::create([
            'name' => 'Mieszkanie 2',
            'address' => 'ul. Morska 25',
            'city' => 'Gda\u0144sk',
            'postal_code' => '80-010',
            'capacity' => 6,
            'description' => 'Spa\u0107niejsze mieszkanie z wi\u0119kszym tarasem'
        ]);

        Accommodation::create([
            'name' => 'Mieszkanie 3',
            'address' => 'ul. Shipyard 5',
            'city' => 'Gda\u0144sk',
            'postal_code' => '80-020',
            'capacity' => 3,
            'description' => 'Ma\u0142e mieszkanie dla pracownik\u00f3w'
        ]);
    }
}
