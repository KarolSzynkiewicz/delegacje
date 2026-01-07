<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrator - ma wszystkie uprawnienia
        // Use DB directly to avoid model validation issues with old columns
        $adminId = \DB::table('user_roles')->where('name', 'administrator')->where('guard_name', 'web')->value('id');
        if (!$adminId) {
            $adminData = [
                'name' => 'administrator',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            // Add slug if column exists (old structure)
            if (Schema::hasColumn('user_roles', 'slug')) {
                $adminData['slug'] = 'administrator';
            }
            $adminId = \DB::table('user_roles')->insertGetId($adminData);
        }
        $admin = Role::find($adminId);

        // Kierownik - może wszystko oprócz usuwania i zarządzania rolami
        $managerId = \DB::table('user_roles')->where('name', 'kierownik')->where('guard_name', 'web')->value('id');
        if (!$managerId) {
            $managerData = [
                'name' => 'kierownik',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('user_roles', 'slug')) {
                $managerData['slug'] = 'kierownik';
            }
            $managerId = \DB::table('user_roles')->insertGetId($managerData);
        }
        $manager = Role::find($managerId);

        // Pracownik biurowy - tylko przeglądanie
        $officeWorkerId = \DB::table('user_roles')->where('name', 'pracownik-biurowy')->where('guard_name', 'web')->value('id');
        if (!$officeWorkerId) {
            $officeWorkerData = [
                'name' => 'pracownik-biurowy',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('user_roles', 'slug')) {
                $officeWorkerData['slug'] = 'pracownik-biurowy';
            }
            $officeWorkerId = \DB::table('user_roles')->insertGetId($officeWorkerData);
        }
        $officeWorker = Role::find($officeWorkerId);

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
            // Nowe funkcjonalności
            'logistics-events.viewAny', 'logistics-events.view', 'logistics-events.create', 'logistics-events.update',
            'equipment.viewAny', 'equipment.view', 'equipment.create', 'equipment.update',
            'equipment-issues.viewAny', 'equipment-issues.view', 'equipment-issues.create', 'equipment-issues.update',
            'transport-costs.viewAny', 'transport-costs.view', 'transport-costs.create', 'transport-costs.update',
            'time-logs.viewAny', 'time-logs.view', 'time-logs.create', 'time-logs.update',
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
            // Nowe funkcjonalności - tylko przeglądanie
            'logistics-events.viewAny', 'logistics-events.view',
            'equipment.viewAny', 'equipment.view',
            'equipment-issues.viewAny', 'equipment-issues.view',
            'transport-costs.viewAny', 'transport-costs.view',
            'time-logs.viewAny', 'time-logs.view',
        ])->get();

        $officeWorker->syncPermissions($officeWorkerPermissions);

        // Administrator ma wszystkie uprawnienia (nie przypisujemy, bo w User modelu admin zawsze ma dostęp)
    }
}
