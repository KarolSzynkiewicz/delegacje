<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            UserRoleSeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            LocationSeeder::class,
            DocumentSeeder::class, // Wymagania formalne (dokumenty)
            EmployeeSeeder::class,
            EmployeeDocumentSeeder::class, // Dokumenty pracowników
            AccommodationSeeder::class,
            VehicleSeeder::class,
            ProjectSeeder::class,
            ProjectDemandSeeder::class, // Zapotrzebowania na projekty
            // Przypisania (project_assignments, vehicle_assignments, accommodation_assignments) 
            // są tworzone ręcznie przez użytkownika w UI z pełną walidacją biznesową
        ]);
    }
}
