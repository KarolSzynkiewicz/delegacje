<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Stocznia Gdańsk',
                'address' => 'ul. Doki 1',
                'city' => 'Gdańsk',
                'postal_code' => '80-958',
                'contact_person' => 'Jan Kowalski',
                'phone' => '+48 58 123 45 67',
                'email' => 'kontakt@stocznia-gdansk.pl',
                'description' => 'Główna stocznia w Gdańsku, specjalizująca się w budowie statków handlowych.',
            ],
            [
                'name' => 'Stocznia Szczecin',
                'address' => 'ul. Portowa 15',
                'city' => 'Szczecin',
                'postal_code' => '70-225',
                'contact_person' => 'Anna Nowak',
                'phone' => '+48 91 234 56 78',
                'email' => 'biuro@stocznia-szczecin.pl',
                'description' => 'Stocznia w Szczecinie, remonty i modernizacje jednostek pływających.',
            ],
            [
                'name' => 'Stocznia Gdynia',
                'address' => 'ul. Stoczniowa 8',
                'city' => 'Gdynia',
                'postal_code' => '81-345',
                'contact_person' => 'Piotr Wiśniewski',
                'phone' => '+48 58 345 67 89',
                'email' => 'info@stocznia-gdynia.pl',
                'description' => 'Stocznia w Gdyni, budowa jachtów i jednostek specjalistycznych.',
            ],
            [
                'name' => 'Stocznia Ustka',
                'address' => 'ul. Morska 22',
                'city' => 'Ustka',
                'postal_code' => '76-270',
                'contact_person' => 'Marek Zieliński',
                'phone' => '+48 59 456 78 90',
                'email' => 'kontakt@stocznia-ustka.pl',
                'description' => 'Mniejsza stocznia w Ustce, remonty i konserwacja jednostek.',
            ],
            [
                'name' => 'Stocznia Remontowa',
                'address' => 'ul. Stoczniowców 1',
                'city' => 'Gdańsk',
                'postal_code' => '80-958',
                'contact_person' => 'Katarzyna Lewandowska',
                'phone' => '+48 58 567 89 01',
                'email' => 'biuro@remontowa.pl',
                'description' => 'Stocznia remontowa w Gdańsku, specjalizująca się w naprawach statków.',
            ],
        ];

        foreach ($locations as $location) {
            Location::firstOrCreate(
                ['name' => $location['name']],
                $location
            );
        }
    }
}

