<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'Spawacz'],
            ['description' => 'Pracownik zajmujący się spawaniem']
        );

        Role::firstOrCreate(
            ['name' => 'Dekarz'],
            ['description' => 'Pracownik zajmujący się pracami dekarskimi']
        );

        Role::firstOrCreate(
            ['name' => 'Elektryk'],
            ['description' => 'Pracownik zajmujący się pracami elektrycznymi']
        );

        Role::firstOrCreate(
            ['name' => 'Operator'],
            ['description' => 'Operator urządzeń i maszyn']
        );
    }
}
