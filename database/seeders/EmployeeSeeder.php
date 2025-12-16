<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Role;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $spawaczRole = Role::where('name', 'Spawacz')->first();
        $dekarzeRole = Role::where('name', 'Dekarz')->first();
        $elektryk = Role::where('name', 'Elektryk')->first();

        Employee::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@example.com',
            'phone' => '+48 123 456 789',
            'role_id' => $spawaczRole->id,
            'a1_valid_from' => '2023-01-15',
            'a1_valid_to' => '2026-01-15',
            'document_1' => 'Certyfikat spawacza',
            'document_2' => 'Prawo jazdy kat. B',
            'document_3' => 'Ubezpieczenie'
        ]);

        Employee::create([
            'first_name' => 'Piotr',
            'last_name' => 'Nowak',
            'email' => 'piotr.nowak@example.com',
            'phone' => '+48 234 567 890',
            'role_id' => $dekarzeRole->id,
            'a1_valid_from' => '2022-06-20',
            'a1_valid_to' => '2025-06-20',
            'document_1' => 'Certyfikat dekarza',
            'document_2' => 'Prawo jazdy kat. B'
        ]);

        Employee::create([
            'first_name' => 'Andrzej',
            'last_name' => 'Lewandowski',
            'email' => 'andrzej.lewandowski@example.com',
            'phone' => '+48 345 678 901',
            'role_id' => $elektryk->id,
            'a1_valid_from' => '2024-03-10',
            'a1_valid_to' => '2027-03-10',
            'document_1' => 'Certyfikat elektyka',
            'document_2' => 'Prawo jazdy kat. B',
            'document_3' => 'Szkolenie BHP'
        ]);
    }
}
