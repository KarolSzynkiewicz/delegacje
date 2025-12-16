<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vehicle::create([
            'registration_number' => 'WA 12345',
            'brand' => 'Volkswagen',
            'model' => 'Transporter',
            'capacity' => 9,
            'technical_condition' => 'good',
            'inspection_valid_to' => '2026-06-15',
            'notes' => 'Pojazd do transportu pracownikow'
        ]);

        Vehicle::create([
            'registration_number' => 'WA 54321',
            'brand' => 'Mercedes',
            'model' => 'Sprinter',
            'capacity' => 12,
            'technical_condition' => 'excellent',
            'inspection_valid_to' => '2027-03-20',
            'notes' => 'Nowy pojazd, idealny do transportu'
        ]);

        Vehicle::create([
            'registration_number' => 'WA 99999',
            'brand' => 'Ford',
            'model' => 'Transit',
            'capacity' => 8,
            'technical_condition' => 'fair',
            'inspection_valid_to' => '2025-12-10',
            'notes' => 'Wymaga serwisu w przyszlosci'
        ]);
    }
}
