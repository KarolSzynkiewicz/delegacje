<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Role;
use App\Models\EmployeeDocument;

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

        $jan = Employee::create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'email' => 'jan.kowalski@example.com',
            'phone' => '+48 123 456 789',
            'role_id' => $spawaczRole->id,
        ]);

        // Dokumenty dla Jana
        EmployeeDocument::create([
            'employee_id' => $jan->id,
            'type' => 'Prawo jazdy A1',
            'valid_from' => '2023-01-15',
            'valid_to' => '2026-01-15',
            'kind' => 'okresowy',
        ]);
        Document::create([
            'employee_id' => $jan->id,
            'type' => 'Certyfikat spawacza',
            'valid_from' => '2023-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);
        Document::create([
            'employee_id' => $jan->id,
            'type' => 'Prawo jazdy kat. B',
            'valid_from' => '2020-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);
        Document::create([
            'employee_id' => $jan->id,
            'type' => 'Ubezpieczenie',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-12-31',
            'kind' => 'okresowy',
        ]);

        $piotr = Employee::create([
            'first_name' => 'Piotr',
            'last_name' => 'Nowak',
            'email' => 'piotr.nowak@example.com',
            'phone' => '+48 234 567 890',
            'role_id' => $dekarzeRole->id,
        ]);

        // Dokumenty dla Piotra
        Document::create([
            'employee_id' => $piotr->id,
            'type' => 'Prawo jazdy A1',
            'valid_from' => '2022-06-20',
            'valid_to' => '2025-06-20',
            'kind' => 'okresowy',
        ]);
        Document::create([
            'employee_id' => $piotr->id,
            'type' => 'Certyfikat dekarza',
            'valid_from' => '2022-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);
        Document::create([
            'employee_id' => $piotr->id,
            'type' => 'Prawo jazdy kat. B',
            'valid_from' => '2019-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);

        $andrzej = Employee::create([
            'first_name' => 'Andrzej',
            'last_name' => 'Lewandowski',
            'email' => 'andrzej.lewandowski@example.com',
            'phone' => '+48 345 678 901',
            'role_id' => $elektryk->id,
        ]);

        // Dokumenty dla Andrzeja
        Document::create([
            'employee_id' => $andrzej->id,
            'type' => 'Prawo jazdy A1',
            'valid_from' => '2024-03-10',
            'valid_to' => '2027-03-10',
            'kind' => 'okresowy',
        ]);
        Document::create([
            'employee_id' => $andrzej->id,
            'type' => 'Certyfikat elektryka',
            'valid_from' => '2023-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);
        Document::create([
            'employee_id' => $andrzej->id,
            'type' => 'Prawo jazdy kat. B',
            'valid_from' => '2021-01-01',
            'valid_to' => null,
            'kind' => 'bezokresowy',
        ]);
        Document::create([
            'employee_id' => $andrzej->id,
            'type' => 'Szkolenie BHP',
            'valid_from' => '2024-01-01',
            'valid_to' => '2025-12-31',
            'kind' => 'okresowy',
        ]);
    }
}
