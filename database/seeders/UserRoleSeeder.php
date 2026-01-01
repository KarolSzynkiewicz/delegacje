<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrator - ma wszystkie uprawnienia
        $admin = UserRole::firstOrCreate(
            ['slug' => 'administrator'],
            [
                'name' => 'Administrator',
                'description' => 'Pełny dostęp do wszystkich funkcji systemu',
            ]
        );

        // Kierownik - może wszystko oprócz usuwania i zarządzania rolami
        $manager = UserRole::firstOrCreate(
            ['slug' => 'kierownik'],
            [
                'name' => 'Kierownik',
                'description' => 'Może przeglądać, tworzyć i edytować większość zasobów',
            ]
        );

        // Pracownik biurowy - tylko przeglądanie
        $officeWorker = UserRole::firstOrCreate(
            ['slug' => 'pracownik-biurowy'],
            [
                'name' => 'Pracownik biurowy',
                'description' => 'Może tylko przeglądać zasoby',
            ]
        );

        // Przypisanie uprawnień dla Kierownika
        $managerPermissions = Permission::whereIn('slug', [
            'projects.viewAny', 'projects.view', 'projects.create', 'projects.update',
            'employees.viewAny', 'employees.view', 'employees.create', 'employees.update',
            'vehicles.viewAny', 'vehicles.view', 'vehicles.create', 'vehicles.update',
            'accommodations.viewAny', 'accommodations.view', 'accommodations.create', 'accommodations.update',
            'locations.viewAny', 'locations.view', 'locations.create', 'locations.update',
            'roles.viewAny', 'roles.view', 'roles.create', 'roles.update',
            'assignments.viewAny', 'assignments.view', 'assignments.create', 'assignments.update',
            'vehicle-assignments.viewAny', 'vehicle-assignments.view', 'vehicle-assignments.create', 'vehicle-assignments.update',
            'accommodation-assignments.viewAny', 'accommodation-assignments.view', 'accommodation-assignments.create', 'accommodation-assignments.update',
            'demands.viewAny', 'demands.view', 'demands.create', 'demands.update',
            'reports.viewAny', 'reports.view', 'reports.create', 'reports.update',
            'weekly-overview.view',
        ])->pluck('id');

        $manager->permissions()->sync($managerPermissions);

        // Przypisanie uprawnień dla Pracownika biurowego
        $officeWorkerPermissions = Permission::whereIn('slug', [
            'projects.viewAny', 'projects.view',
            'employees.viewAny', 'employees.view',
            'vehicles.viewAny', 'vehicles.view',
            'accommodations.viewAny', 'accommodations.view',
            'locations.viewAny', 'locations.view',
            'roles.viewAny', 'roles.view',
            'assignments.viewAny', 'assignments.view',
            'vehicle-assignments.viewAny', 'vehicle-assignments.view',
            'accommodation-assignments.viewAny', 'accommodation-assignments.view',
            'demands.viewAny', 'demands.view',
            'reports.viewAny', 'reports.view',
            'weekly-overview.view',
        ])->pluck('id');

        $officeWorker->permissions()->sync($officeWorkerPermissions);

        // Administrator ma wszystkie uprawnienia (nie przypisujemy, bo w User modelu admin zawsze ma dostęp)
    }
}
