<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
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

            // Profitability Dashboard
            ['name' => 'Przeglądanie dashboardu rentowności', 'slug' => 'profitability.viewAny', 'model' => 'profitability', 'action' => 'viewAny', 'description' => 'Może przeglądać dashboard rentowności'],

            // User Roles Management (zarządzanie rolami użytkowników)
            ['name' => 'Przeglądanie ról użytkowników', 'slug' => 'user-roles.viewAny', 'model' => 'user-roles', 'action' => 'viewAny', 'description' => 'Może przeglądać listę ról użytkowników'],
            ['name' => 'Szczegóły roli użytkownika', 'slug' => 'user-roles.view', 'model' => 'user-roles', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły roli użytkownika'],
            ['name' => 'Tworzenie ról użytkowników', 'slug' => 'user-roles.create', 'model' => 'user-roles', 'action' => 'create', 'description' => 'Może tworzyć nowe role użytkowników'],
            ['name' => 'Edycja ról użytkowników', 'slug' => 'user-roles.update', 'model' => 'user-roles', 'action' => 'update', 'description' => 'Może edytować role użytkowników'],
            ['name' => 'Usuwanie ról użytkowników', 'slug' => 'user-roles.delete', 'model' => 'user-roles', 'action' => 'delete', 'description' => 'Może usuwać role użytkowników'],

            // Users Management (zarządzanie użytkownikami)
            ['name' => 'Przeglądanie użytkowników', 'slug' => 'users.viewAny', 'model' => 'users', 'action' => 'viewAny', 'description' => 'Może przeglądać listę użytkowników'],
            ['name' => 'Szczegóły użytkownika', 'slug' => 'users.view', 'model' => 'users', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły użytkownika'],
            ['name' => 'Tworzenie użytkowników', 'slug' => 'users.create', 'model' => 'users', 'action' => 'create', 'description' => 'Może tworzyć nowych użytkowników'],
            ['name' => 'Edycja użytkowników', 'slug' => 'users.update', 'model' => 'users', 'action' => 'update', 'description' => 'Może edytować użytkowników'],
            ['name' => 'Usuwanie użytkowników', 'slug' => 'users.delete', 'model' => 'users', 'action' => 'delete', 'description' => 'Może usuwać użytkowników'],

            // Logistics Events (Zjazdy/Wyjazdy)
            ['name' => 'Przeglądanie zdarzeń logistycznych', 'slug' => 'logistics-events.viewAny', 'model' => 'logistics-events', 'action' => 'viewAny', 'description' => 'Może przeglądać listę zdarzeń logistycznych'],
            ['name' => 'Szczegóły zdarzenia logistycznego', 'slug' => 'logistics-events.view', 'model' => 'logistics-events', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły zdarzenia logistycznego'],
            ['name' => 'Tworzenie zdarzeń logistycznych', 'slug' => 'logistics-events.create', 'model' => 'logistics-events', 'action' => 'create', 'description' => 'Może tworzyć nowe zdarzenia logistyczne'],
            ['name' => 'Edycja zdarzeń logistycznych', 'slug' => 'logistics-events.update', 'model' => 'logistics-events', 'action' => 'update', 'description' => 'Może edytować zdarzenia logistyczne'],
            ['name' => 'Usuwanie zdarzeń logistycznych', 'slug' => 'logistics-events.delete', 'model' => 'logistics-events', 'action' => 'delete', 'description' => 'Może usuwać zdarzenia logistyczne'],

            // Equipment
            ['name' => 'Przeglądanie sprzętu', 'slug' => 'equipment.viewAny', 'model' => 'equipment', 'action' => 'viewAny', 'description' => 'Może przeglądać listę sprzętu'],
            ['name' => 'Szczegóły sprzętu', 'slug' => 'equipment.view', 'model' => 'equipment', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły sprzętu'],
            ['name' => 'Tworzenie sprzętu', 'slug' => 'equipment.create', 'model' => 'equipment', 'action' => 'create', 'description' => 'Może tworzyć nowy sprzęt'],
            ['name' => 'Edycja sprzętu', 'slug' => 'equipment.update', 'model' => 'equipment', 'action' => 'update', 'description' => 'Może edytować sprzęt'],
            ['name' => 'Usuwanie sprzętu', 'slug' => 'equipment.delete', 'model' => 'equipment', 'action' => 'delete', 'description' => 'Może usuwać sprzęt'],

            // Equipment Issues
            ['name' => 'Przeglądanie wydań sprzętu', 'slug' => 'equipment-issues.viewAny', 'model' => 'equipment-issues', 'action' => 'viewAny', 'description' => 'Może przeglądać listę wydań sprzętu'],
            ['name' => 'Szczegóły wydania sprzętu', 'slug' => 'equipment-issues.view', 'model' => 'equipment-issues', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły wydania sprzętu'],
            ['name' => 'Tworzenie wydań sprzętu', 'slug' => 'equipment-issues.create', 'model' => 'equipment-issues', 'action' => 'create', 'description' => 'Może tworzyć nowe wydania sprzętu'],
            ['name' => 'Edycja wydań sprzętu', 'slug' => 'equipment-issues.update', 'model' => 'equipment-issues', 'action' => 'update', 'description' => 'Może edytować wydania sprzętu'],
            ['name' => 'Usuwanie wydań sprzętu', 'slug' => 'equipment-issues.delete', 'model' => 'equipment-issues', 'action' => 'delete', 'description' => 'Może usuwać wydania sprzętu'],

            // Transport Costs
            ['name' => 'Przeglądanie kosztów transportu', 'slug' => 'transport-costs.viewAny', 'model' => 'transport-costs', 'action' => 'viewAny', 'description' => 'Może przeglądać listę kosztów transportu'],
            ['name' => 'Szczegóły kosztu transportu', 'slug' => 'transport-costs.view', 'model' => 'transport-costs', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły kosztu transportu'],
            ['name' => 'Tworzenie kosztów transportu', 'slug' => 'transport-costs.create', 'model' => 'transport-costs', 'action' => 'create', 'description' => 'Może tworzyć nowe koszty transportu'],
            ['name' => 'Edycja kosztów transportu', 'slug' => 'transport-costs.update', 'model' => 'transport-costs', 'action' => 'update', 'description' => 'Może edytować koszty transportu'],
            ['name' => 'Usuwanie kosztów transportu', 'slug' => 'transport-costs.delete', 'model' => 'transport-costs', 'action' => 'delete', 'description' => 'Może usuwać koszty transportu'],

            // Time Logs
            ['name' => 'Przeglądanie ewidencji godzin', 'slug' => 'time-logs.viewAny', 'model' => 'time-logs', 'action' => 'viewAny', 'description' => 'Może przeglądać listę ewidencji godzin'],
            ['name' => 'Szczegóły ewidencji godzin', 'slug' => 'time-logs.view', 'model' => 'time-logs', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły ewidencji godzin'],
            ['name' => 'Tworzenie ewidencji godzin', 'slug' => 'time-logs.create', 'model' => 'time-logs', 'action' => 'create', 'description' => 'Może tworzyć nowe wpisy ewidencji godzin'],
            ['name' => 'Edycja ewidencji godzin', 'slug' => 'time-logs.update', 'model' => 'time-logs', 'action' => 'update', 'description' => 'Może edytować wpisy ewidencji godzin'],
            ['name' => 'Usuwanie ewidencji godzin', 'slug' => 'time-logs.delete', 'model' => 'time-logs', 'action' => 'delete', 'description' => 'Może usuwać wpisy ewidencji godzin'],

            // Adjustments (Kary/Nagrody)
            ['name' => 'Przeglądanie kar i nagród', 'slug' => 'adjustments.viewAny', 'model' => 'adjustments', 'action' => 'viewAny', 'description' => 'Może przeglądać listę kar i nagród'],
            ['name' => 'Szczegóły kary/nagrody', 'slug' => 'adjustments.view', 'model' => 'adjustments', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły kary/nagrody'],
            ['name' => 'Tworzenie kar i nagród', 'slug' => 'adjustments.create', 'model' => 'adjustments', 'action' => 'create', 'description' => 'Może tworzyć nowe kary i nagrody'],
            ['name' => 'Edycja kar i nagród', 'slug' => 'adjustments.update', 'model' => 'adjustments', 'action' => 'update', 'description' => 'Może edytować kary i nagrody'],
            ['name' => 'Usuwanie kar i nagród', 'slug' => 'adjustments.delete', 'model' => 'adjustments', 'action' => 'delete', 'description' => 'Może usuwać kary i nagrody'],

            // Advances (Zaliczki)
            ['name' => 'Przeglądanie zaliczek', 'slug' => 'advances.viewAny', 'model' => 'advances', 'action' => 'viewAny', 'description' => 'Może przeglądać listę zaliczek'],
            ['name' => 'Szczegóły zaliczki', 'slug' => 'advances.view', 'model' => 'advances', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły zaliczki'],
            ['name' => 'Tworzenie zaliczek', 'slug' => 'advances.create', 'model' => 'advances', 'action' => 'create', 'description' => 'Może tworzyć nowe zaliczki'],
            ['name' => 'Edycja zaliczek', 'slug' => 'advances.update', 'model' => 'advances', 'action' => 'update', 'description' => 'Może edytować zaliczki'],
            ['name' => 'Usuwanie zaliczek', 'slug' => 'advances.delete', 'model' => 'advances', 'action' => 'delete', 'description' => 'Może usuwać zaliczki'],

            // Documents (Słownik dokumentów)
            ['name' => 'Przeglądanie dokumentów', 'slug' => 'documents.viewAny', 'model' => 'documents', 'action' => 'viewAny', 'description' => 'Może przeglądać listę dokumentów'],
            ['name' => 'Szczegóły dokumentu', 'slug' => 'documents.view', 'model' => 'documents', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły dokumentu'],
            ['name' => 'Tworzenie dokumentów', 'slug' => 'documents.create', 'model' => 'documents', 'action' => 'create', 'description' => 'Może tworzyć nowe dokumenty'],
            ['name' => 'Edycja dokumentów', 'slug' => 'documents.update', 'model' => 'documents', 'action' => 'update', 'description' => 'Może edytować dokumenty'],
            ['name' => 'Usuwanie dokumentów', 'slug' => 'documents.delete', 'model' => 'documents', 'action' => 'delete', 'description' => 'Może usuwać dokumenty'],

            // Employee Documents (Dokumenty pracowników)
            ['name' => 'Przeglądanie dokumentów pracowników', 'slug' => 'employee-documents.viewAny', 'model' => 'employee-documents', 'action' => 'viewAny', 'description' => 'Może przeglądać listę dokumentów pracowników'],
            ['name' => 'Tworzenie dokumentów pracowników', 'slug' => 'employee-documents.create', 'model' => 'employee-documents', 'action' => 'create', 'description' => 'Może tworzyć nowe dokumenty pracowników'],
            ['name' => 'Edycja dokumentów pracowników', 'slug' => 'employee-documents.update', 'model' => 'employee-documents', 'action' => 'update', 'description' => 'Może edytować dokumenty pracowników'],
            ['name' => 'Usuwanie dokumentów pracowników', 'slug' => 'employee-documents.delete', 'model' => 'employee-documents', 'action' => 'delete', 'description' => 'Może usuwać dokumenty pracowników'],

            // Employee Rates (Stawki pracowników)
            ['name' => 'Przeglądanie stawek pracowników', 'slug' => 'employee-rates.viewAny', 'model' => 'employee-rates', 'action' => 'viewAny', 'description' => 'Może przeglądać listę stawek pracowników'],
            ['name' => 'Szczegóły stawki pracownika', 'slug' => 'employee-rates.view', 'model' => 'employee-rates', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły stawki pracownika'],
            ['name' => 'Tworzenie stawek pracowników', 'slug' => 'employee-rates.create', 'model' => 'employee-rates', 'action' => 'create', 'description' => 'Może tworzyć nowe stawki pracowników'],
            ['name' => 'Edycja stawek pracowników', 'slug' => 'employee-rates.update', 'model' => 'employee-rates', 'action' => 'update', 'description' => 'Może edytować stawki pracowników'],
            ['name' => 'Usuwanie stawek pracowników', 'slug' => 'employee-rates.delete', 'model' => 'employee-rates', 'action' => 'delete', 'description' => 'Może usuwać stawki pracowników'],

            // Fixed Costs (Koszty stałe)
            ['name' => 'Przeglądanie kosztów stałych', 'slug' => 'fixed-costs.viewAny', 'model' => 'fixed-costs', 'action' => 'viewAny', 'description' => 'Może przeglądać listę kosztów stałych'],
            ['name' => 'Szczegóły kosztu stałego', 'slug' => 'fixed-costs.view', 'model' => 'fixed-costs', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły kosztu stałego'],
            ['name' => 'Tworzenie kosztów stałych', 'slug' => 'fixed-costs.create', 'model' => 'fixed-costs', 'action' => 'create', 'description' => 'Może tworzyć nowe koszty stałe'],
            ['name' => 'Edycja kosztów stałych', 'slug' => 'fixed-costs.update', 'model' => 'fixed-costs', 'action' => 'update', 'description' => 'Może edytować koszty stałe'],
            ['name' => 'Usuwanie kosztów stałych', 'slug' => 'fixed-costs.delete', 'model' => 'fixed-costs', 'action' => 'delete', 'description' => 'Może usuwać koszty stałe'],

            // Payrolls (Payroll)
            ['name' => 'Przeglądanie payroll', 'slug' => 'payrolls.viewAny', 'model' => 'payrolls', 'action' => 'viewAny', 'description' => 'Może przeglądać listę payroll'],
            ['name' => 'Szczegóły payroll', 'slug' => 'payrolls.view', 'model' => 'payrolls', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły payroll'],
            ['name' => 'Tworzenie payroll', 'slug' => 'payrolls.create', 'model' => 'payrolls', 'action' => 'create', 'description' => 'Może tworzyć nowe payroll'],
            ['name' => 'Edycja payroll', 'slug' => 'payrolls.update', 'model' => 'payrolls', 'action' => 'update', 'description' => 'Może edytować payroll'],
            ['name' => 'Usuwanie payroll', 'slug' => 'payrolls.delete', 'model' => 'payrolls', 'action' => 'delete', 'description' => 'Może usuwać payroll'],

            // Project Variable Costs (Koszty zmienne projektów)
            ['name' => 'Przeglądanie kosztów zmiennych projektów', 'slug' => 'project-variable-costs.viewAny', 'model' => 'project-variable-costs', 'action' => 'viewAny', 'description' => 'Może przeglądać listę kosztów zmiennych projektów'],
            ['name' => 'Szczegóły kosztu zmiennego projektu', 'slug' => 'project-variable-costs.view', 'model' => 'project-variable-costs', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły kosztu zmiennego projektu'],
            ['name' => 'Tworzenie kosztów zmiennych projektów', 'slug' => 'project-variable-costs.create', 'model' => 'project-variable-costs', 'action' => 'create', 'description' => 'Może tworzyć nowe koszty zmienne projektów'],
            ['name' => 'Edycja kosztów zmiennych projektów', 'slug' => 'project-variable-costs.update', 'model' => 'project-variable-costs', 'action' => 'update', 'description' => 'Może edytować koszty zmienne projektów'],
            ['name' => 'Usuwanie kosztów zmiennych projektów', 'slug' => 'project-variable-costs.delete', 'model' => 'project-variable-costs', 'action' => 'delete', 'description' => 'Może usuwać koszty zmienne projektów'],

            // Rotations (Rotacje)
            ['name' => 'Przeglądanie rotacji', 'slug' => 'rotations.viewAny', 'model' => 'rotations', 'action' => 'viewAny', 'description' => 'Może przeglądać listę rotacji'],
            ['name' => 'Szczegóły rotacji', 'slug' => 'rotations.view', 'model' => 'rotations', 'action' => 'view', 'description' => 'Może zobaczyć szczegóły rotacji'],
            ['name' => 'Tworzenie rotacji', 'slug' => 'rotations.create', 'model' => 'rotations', 'action' => 'create', 'description' => 'Może tworzyć nowe rotacje'],
            ['name' => 'Edycja rotacji', 'slug' => 'rotations.update', 'model' => 'rotations', 'action' => 'update', 'description' => 'Może edytować rotacje'],
            ['name' => 'Usuwanie rotacji', 'slug' => 'rotations.delete', 'model' => 'rotations', 'action' => 'delete', 'description' => 'Może usuwać rotacje'],

        ];

        foreach ($permissions as $permission) {
            // Use DB directly to avoid model validation issues with old columns
            if (!\DB::table('permissions')->where('name', $permission['slug'])->where('guard_name', 'web')->exists()) {
                \DB::table('permissions')->insert([
                    'name' => $permission['slug'],
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
