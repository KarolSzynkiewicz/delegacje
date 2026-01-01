<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Projects
            ['name' => 'Przeglądanie projektów', 'slug' => 'projects.viewAny', 'model' => 'projects', 'action' => 'viewAny', 'description' => 'Może przeglądać listę projektów'],
            ['name' => 'Szczegóły projektu', 'slug' => 'projects.view', 'model' => 'projects', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły projektu'],
            ['name' => 'Tworzenie projektów', 'slug' => 'projects.create', 'model' => 'projects', 'action' => 'create', 'description' => 'Może tworzyć nowe projekty'],
            ['name' => 'Edycja projektów', 'slug' => 'projects.update', 'model' => 'projects', 'action' => 'update', 'description' => 'Może edytować projekty'],
            ['name' => 'Usuwanie projektów', 'slug' => 'projects.delete', 'model' => 'projects', 'action' => 'delete', 'description' => 'Może usuwać projekty'],

            // Employees
            ['name' => 'Przeglądanie pracowników', 'slug' => 'employees.viewAny', 'model' => 'employees', 'action' => 'viewAny', 'description' => 'Może przeglądać listę pracowników'],
            ['name' => 'Szczegóły pracownika', 'slug' => 'employees.view', 'model' => 'employees', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły pracownika'],
            ['name' => 'Tworzenie pracowników', 'slug' => 'employees.create', 'model' => 'employees', 'action' => 'create', 'description' => 'Może tworzyć nowych pracowników'],
            ['name' => 'Edycja pracowników', 'slug' => 'employees.update', 'model' => 'employees', 'action' => 'update', 'description' => 'Może edytować pracowników'],
            ['name' => 'Usuwanie pracowników', 'slug' => 'employees.delete', 'model' => 'employees', 'action' => 'delete', 'description' => 'Może usuwać pracowników'],

            // Vehicles
            ['name' => 'Przeglądanie pojazdów', 'slug' => 'vehicles.viewAny', 'model' => 'vehicles', 'action' => 'viewAny', 'description' => 'Może przeglądać listę pojazdów'],
            ['name' => 'Szczegóły pojazdu', 'slug' => 'vehicles.view', 'model' => 'vehicles', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły pojazdu'],
            ['name' => 'Tworzenie pojazdów', 'slug' => 'vehicles.create', 'model' => 'vehicles', 'action' => 'create', 'description' => 'Może tworzyć nowe pojazdy'],
            ['name' => 'Edycja pojazdów', 'slug' => 'vehicles.update', 'model' => 'vehicles', 'action' => 'update', 'description' => 'Może edytować pojazdy'],
            ['name' => 'Usuwanie pojazdów', 'slug' => 'vehicles.delete', 'model' => 'vehicles', 'action' => 'delete', 'description' => 'Może usuwać pojazdy'],

            // Accommodations
            ['name' => 'Przeglądanie mieszkań', 'slug' => 'accommodations.viewAny', 'model' => 'accommodations', 'action' => 'viewAny', 'description' => 'Może przeglądać listę mieszkań'],
            ['name' => 'Szczegóły mieszkania', 'slug' => 'accommodations.view', 'model' => 'accommodations', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły mieszkania'],
            ['name' => 'Tworzenie mieszkań', 'slug' => 'accommodations.create', 'model' => 'accommodations', 'action' => 'create', 'description' => 'Może tworzyć nowe mieszkania'],
            ['name' => 'Edycja mieszkań', 'slug' => 'accommodations.update', 'model' => 'accommodations', 'action' => 'update', 'description' => 'Może edytować mieszkania'],
            ['name' => 'Usuwanie mieszkań', 'slug' => 'accommodations.delete', 'model' => 'accommodations', 'action' => 'delete', 'description' => 'Może usuwać mieszkania'],

            // Locations
            ['name' => 'Przeglądanie lokalizacji', 'slug' => 'locations.viewAny', 'model' => 'locations', 'action' => 'viewAny', 'description' => 'Może przeglądać listę lokalizacji'],
            ['name' => 'Szczegóły lokalizacji', 'slug' => 'locations.view', 'model' => 'locations', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły lokalizacji'],
            ['name' => 'Tworzenie lokalizacji', 'slug' => 'locations.create', 'model' => 'locations', 'action' => 'create', 'description' => 'Może tworzyć nowe lokalizacje'],
            ['name' => 'Edycja lokalizacji', 'slug' => 'locations.update', 'model' => 'locations', 'action' => 'update', 'description' => 'Może edytować lokalizacje'],
            ['name' => 'Usuwanie lokalizacji', 'slug' => 'locations.delete', 'model' => 'locations', 'action' => 'delete', 'description' => 'Może usuwać lokalizacje'],

            // Roles (pracowników w projektach)
            ['name' => 'Przeglądanie ról', 'slug' => 'roles.viewAny', 'model' => 'roles', 'action' => 'viewAny', 'description' => 'Może przeglądać listę ról'],
            ['name' => 'Szczegóły roli', 'slug' => 'roles.view', 'model' => 'roles', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły roli'],
            ['name' => 'Tworzenie ról', 'slug' => 'roles.create', 'model' => 'roles', 'action' => 'create', 'description' => 'Może tworzyć nowe role'],
            ['name' => 'Edycja ról', 'slug' => 'roles.update', 'model' => 'roles', 'action' => 'update', 'description' => 'Może edytować role'],
            ['name' => 'Usuwanie ról', 'slug' => 'roles.delete', 'model' => 'roles', 'action' => 'delete', 'description' => 'Może usuwać role'],

            // Project Assignments
            ['name' => 'Przeglądanie przypisań', 'slug' => 'assignments.viewAny', 'model' => 'assignments', 'action' => 'viewAny', 'description' => 'Może przeglądać listę przypisań'],
            ['name' => 'Szczegóły przypisania', 'slug' => 'assignments.view', 'model' => 'assignments', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły przypisania'],
            ['name' => 'Tworzenie przypisań', 'slug' => 'assignments.create', 'model' => 'assignments', 'action' => 'create', 'description' => 'Może tworzyć nowe przypisania'],
            ['name' => 'Edycja przypisań', 'slug' => 'assignments.update', 'model' => 'assignments', 'action' => 'update', 'description' => 'Może edytować przypisania'],
            ['name' => 'Usuwanie przypisań', 'slug' => 'assignments.delete', 'model' => 'assignments', 'action' => 'delete', 'description' => 'Może usuwać przypisania'],

            // Vehicle Assignments
            ['name' => 'Przeglądanie przypisań pojazdów', 'slug' => 'vehicle-assignments.viewAny', 'model' => 'vehicle-assignments', 'action' => 'viewAny', 'description' => 'Może przeglądać listę przypisań pojazdów'],
            ['name' => 'Szczegóły przypisania pojazdu', 'slug' => 'vehicle-assignments.view', 'model' => 'vehicle-assignments', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły przypisania pojazdu'],
            ['name' => 'Tworzenie przypisań pojazdów', 'slug' => 'vehicle-assignments.create', 'model' => 'vehicle-assignments', 'action' => 'create', 'description' => 'Może tworzyć nowe przypisania pojazdów'],
            ['name' => 'Edycja przypisań pojazdów', 'slug' => 'vehicle-assignments.update', 'model' => 'vehicle-assignments', 'action' => 'update', 'description' => 'Może edytować przypisania pojazdów'],
            ['name' => 'Usuwanie przypisań pojazdów', 'slug' => 'vehicle-assignments.delete', 'model' => 'vehicle-assignments', 'action' => 'delete', 'description' => 'Może usuwać przypisania pojazdów'],

            // Accommodation Assignments
            ['name' => 'Przeglądanie przypisań mieszkań', 'slug' => 'accommodation-assignments.viewAny', 'model' => 'accommodation-assignments', 'action' => 'viewAny', 'description' => 'Może przeglądać listę przypisań mieszkań'],
            ['name' => 'Szczegóły przypisania mieszkania', 'slug' => 'accommodation-assignments.view', 'model' => 'accommodation-assignments', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły przypisania mieszkania'],
            ['name' => 'Tworzenie przypisań mieszkań', 'slug' => 'accommodation-assignments.create', 'model' => 'accommodation-assignments', 'action' => 'create', 'description' => 'Może tworzyć nowe przypisania mieszkań'],
            ['name' => 'Edycja przypisań mieszkań', 'slug' => 'accommodation-assignments.update', 'model' => 'accommodation-assignments', 'action' => 'update', 'description' => 'Może edytować przypisania mieszkań'],
            ['name' => 'Usuwanie przypisań mieszkań', 'slug' => 'accommodation-assignments.delete', 'model' => 'accommodation-assignments', 'action' => 'delete', 'description' => 'Może usuwać przypisania mieszkań'],

            // Project Demands
            ['name' => 'Przeglądanie zapotrzebowań', 'slug' => 'demands.viewAny', 'model' => 'demands', 'action' => 'viewAny', 'description' => 'Może przeglądać listę zapotrzebowań'],
            ['name' => 'Szczegóły zapotrzebowania', 'slug' => 'demands.view', 'model' => 'demands', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły zapotrzebowania'],
            ['name' => 'Tworzenie zapotrzebowań', 'slug' => 'demands.create', 'model' => 'demands', 'action' => 'create', 'description' => 'Może tworzyć nowe zapotrzebowania'],
            ['name' => 'Edycja zapotrzebowań', 'slug' => 'demands.update', 'model' => 'demands', 'action' => 'update', 'description' => 'Może edytować zapotrzebowania'],
            ['name' => 'Usuwanie zapotrzebowań', 'slug' => 'demands.delete', 'model' => 'demands', 'action' => 'delete', 'description' => 'Może usuwać zapotrzebowania'],

            // Reports
            ['name' => 'Przeglądanie raportów', 'slug' => 'reports.viewAny', 'model' => 'reports', 'action' => 'viewAny', 'description' => 'Może przeglądać listę raportów'],
            ['name' => 'Szczegóły raportu', 'slug' => 'reports.view', 'model' => 'reports', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły raportu'],
            ['name' => 'Tworzenie raportów', 'slug' => 'reports.create', 'model' => 'reports', 'action' => 'create', 'description' => 'Może tworzyć nowe raporty'],
            ['name' => 'Edycja raportów', 'slug' => 'reports.update', 'model' => 'reports', 'action' => 'update', 'description' => 'Może edytować raporty'],
            ['name' => 'Usuwanie raportów', 'slug' => 'reports.delete', 'model' => 'reports', 'action' => 'delete', 'description' => 'Może usuwać raporty'],

            // Weekly Overview
            ['name' => 'Przeglądanie planera tygodniowego', 'slug' => 'weekly-overview.view', 'model' => 'weekly-overview', 'action' => 'view', 'description' => 'Może przeglądać planer tygodniowy'],

            // User Roles Management (zarządzanie rolami użytkowników)
            ['name' => 'Przeglądanie ról użytkowników', 'slug' => 'user-roles.viewAny', 'model' => 'user-roles', 'action' => 'viewAny', 'description' => 'Może przeglądać listę ról użytkowników'],
            ['name' => 'Szczegóły roli użytkownika', 'slug' => 'user-roles.view', 'model' => 'user-roles', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły roli użytkownika'],
            ['name' => 'Tworzenie ról użytkowników', 'slug' => 'user-roles.create', 'model' => 'user-roles', 'action' => 'create', 'description' => 'Może tworzyć nowe role użytkowników'],
            ['name' => 'Edycja ról użytkowników', 'slug' => 'user-roles.update', 'model' => 'user-roles', 'action' => 'update', 'description' => 'Może edytować role użytkowników'],
            ['name' => 'Usuwanie ról użytkowników', 'slug' => 'user-roles.delete', 'model' => 'user-roles', 'action' => 'delete', 'description' => 'Może usuwać role użytkowników'],

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
