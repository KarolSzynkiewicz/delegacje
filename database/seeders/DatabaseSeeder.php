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
            EmployeeSeeder::class,
            AccommodationSeeder::class,
            VehicleSeeder::class,
            ProjectSeeder::class,
            WeeklyOverviewSeeder::class,
        ]);
    }
}
