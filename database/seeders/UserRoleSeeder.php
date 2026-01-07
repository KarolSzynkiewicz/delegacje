<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrator - ma wszystkie uprawnienia
        $admin = Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web'],
            ['name' => 'administrator', 'guard_name' => 'web']
        );

        // Kierownik - może wszystko oprócz usuwania i zarządzania rolami
        $manager = Role::firstOrCreate(
            ['name' => 'kierownik', 'guard_name' => 'web'],
            ['name' => 'kierownik', 'guard_name' => 'web']
        );

        // Pracownik biurowy - tylko przeglądanie
        $officeWorker = Role::firstOrCreate(
            ['name' => 'pracownik-biurowy', 'guard_name' => 'web'],
            ['name' => 'pracownik-biurowy', 'guard_name' => 'web']
        );

        // Przypisanie uprawnień dla Kierownika
        $managerPermissions = Permission::whereIn('name', [
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
        ])->get();

        $manager->syncPermissions($managerPermissions);

        // Przypisanie uprawnień dla Pracownika biurowego
        $officeWorkerPermissions = Permission::whereIn('name', [
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
        ])->get();

        $officeWorker->syncPermissions($officeWorkerPermissions);

        // Administrator ma wszystkie uprawnienia (nie przypisujemy, bo w User modelu admin zawsze ma dostęp)
    }
}
