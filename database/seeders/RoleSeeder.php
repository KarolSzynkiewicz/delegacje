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
        Role::create([
            'name' => 'Spawacz',
            'description' => 'Pracownik zajmuj\u0105cy si\u0119 spawaniem'
        ]);

        Role::create([
            'name' => 'Dekarz',
            'description' => 'Pracownik zajmuj\u0105cy si\u0119 pracami dekarskimi'
        ]);

        Role::create([
            'name' => 'Elektryk',
            'description' => 'Pracownik zajmuj\u0105cy si\u0119 pracami elektrycznymi'
        ]);

        Role::create([
            'name' => 'Operator',
            'description' => 'Operator urz\u0105dze\u0144 i maszyn'
        ]);
    }
}
