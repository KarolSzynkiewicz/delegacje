<?php

return [
    'dashboard' => [
        'route' => 'profitability.index',
        'icon' => 'bi bi-graph-up-arrow',
        'label' => 'Dashboard',
    ],

    'weekly_overview' => [
        'route' => 'weekly-overview.index',
        'icon' => 'bi bi-calendar-week',
        'label' => 'Przegląd Tygodniowy',
    ],

    'tasks' => [
        'route' => 'tasks.home',
        'icon' => 'bi bi-list-check',
        'label' => 'Zadania',
    ],

    'procedures' => [
        'route' => 'procedure-templates.index',
        'icon' => 'bi bi-diagram-3',
        'label' => 'Procedury',
    ],

    'projects' => [
        'route' => 'projects.index',
        'icon' => 'bi bi-folder',
        'label' => 'Projekty',
    ],

    'vehicles' => [
        'route' => 'vehicles.index',
        'icon' => 'bi bi-car-front',
        'label' => 'Pojazdy',
    ],

    'vehicle_repairs' => [
        'route' => 'vehicle-repairs.index',
        'icon' => 'bi bi-wrench-adjustable',
        'label' => 'Serwisowanie pojazdów',
        // To samo co „Przypisania pojazdów” — bez osobnej roli dla vehicle-repairs.*
        'permission' => 'vehicle-assignments.view',
    ],

    'accommodations' => [
        'route' => 'accommodations.index',
        'icon' => 'bi bi-house',
        'label' => 'Mieszkania',
    ],

    'companies' => [
        'route' => 'companies.index',
        'icon' => 'bi bi-building',
        'label' => 'Spółki',
    ],

    'warehouses' => [
        'route' => 'warehouses.index',
        'icon' => 'bi bi-buildings',
        'label' => 'Magazyny',
        'permission' => 'equipment.view',
    ],

    'locations' => [
        'route' => 'locations.index',
        'icon' => 'bi bi-geo-alt',
        'label' => 'Lokalizacje',
    ],

    'assignments' => [
        'route' => 'project-assignments.index',
        'icon' => 'bi bi-person-check',
        'label' => 'Przypisania pracowników',
    ],

    'vehicle_assignments' => [
        'route' => 'vehicle-assignments.index',
        'icon' => 'bi bi-car-front-fill',
        'label' => 'Przypisania pojazdów',
    ],

    'accommodation_assignments' => [
        'route' => 'accommodation-assignments.index',
        'icon' => 'bi bi-house-fill',
        'label' => 'Przypisania mieszkań',
    ],

    'company_assignments' => [
        'route' => 'company-assignments.index',
        'icon' => 'bi bi-building-fill',
        'label' => 'Przypisania do spółek',
    ],

    'demands' => [
        'route' => 'project-demands.index',
        'icon' => 'bi bi-clipboard-data',
        'label' => 'Zapotrzebowania projektów',
    ],

    'return_trips' => [
        'route' => 'return-trips.index',
        'icon' => 'bi bi-arrow-left-right',
        'label' => 'Zjazdy',
    ],

    'departures' => [
        'route' => 'departures.index',
        'icon' => 'bi bi-arrow-right',
        'label' => 'Wyjazdy',
    ],

    'transfers' => [
        'route' => 'transfers.index',
        'icon' => 'bi bi-shuffle',
        'label' => 'Transfery',
    ],

    'equipment' => [
        'route' => 'equipment.index',
        'icon' => 'bi bi-box-seam',
        'label' => 'Stany magazynowe',
    ],

    'equipment_issues' => [
        'route' => 'equipment-issues.index',
        'icon' => 'bi bi-box-arrow-up',
        'label' => 'Wydania z magazynu',
    ],

    'equipment_consumptions' => [
        'route' => 'equipment-consumptions.index',
        'icon' => 'bi bi-dash-circle',
        'label' => 'Rozchód z magazynu',
    ],

    'project_variable_costs' => [
        'route' => 'project-variable-costs.index',
        'icon' => 'bi bi-arrow-repeat',
        'label' => 'Koszty projektowe',
    ],

    'transport_costs' => [
        'route' => 'transport-costs.index',
        'icon' => 'bi bi-truck',
        'label' => 'Koszty transportu',
    ],

    'fixed_costs' => [
        'route' => 'fixed-costs.index',
        'icon' => 'bi bi-lock',
        'label' => 'Koszty ogólnofirmowe',
    ],

    'fixed_cost_entries' => [
        'route' => 'fixed-cost-entries.index',
        'icon' => 'bi bi-file-earmark-text',
        'label' => 'Koszty księgowe',
    ],

    'exchange_rates' => [
        'route' => 'exchange-rates.index',
        'icon' => 'bi bi-currency-exchange',
        'label' => 'Kursy walut',
    ],

    'employee_evaluations' => [
        'route' => 'employee-evaluations.index',
        'icon' => 'bi bi-star',
        'label' => 'Oceny pracowników',
    ],

    'employees' => [
        'route' => 'employees.index',
        'icon' => 'bi bi-people',
        'label' => 'Pracownicy',
    ],

    'recruitment_applications' => [
        'route' => 'recruitment-processes.index',
        'icon' => 'bi bi-person-lines-fill',
        'label' => 'Rekrutacja',
        'permission' => 'employees.view',
        'routePattern' => 'recruitment-processes.*',
    ],

    'recruitment_analytics' => [
        'route' => 'recruitment-analytics.index',
        'icon' => 'bi bi-graph-up-arrow',
        'label' => 'Analityka rekrutacji',
        'permission' => 'employees.view',
        'routePattern' => 'recruitment-analytics.*',
    ],

    'roles' => [
        'route' => 'roles.index',
        'icon' => 'bi bi-person-badge',
        'label' => 'Role pracowników',
    ],

    'adjustments' => [
        'route' => 'adjustments.index',
        'icon' => 'bi bi-award',
        'label' => 'Obciążenia i uznania',
    ],

    'time_logs' => [
        'route' => 'time-logs.index',
        'icon' => 'bi bi-clock',
        'label' => 'Ewidencje godzin',
    ],

    'payrolls' => [
        'route' => 'payrolls.index',
        'icon' => 'bi bi-cash-stack',
        'label' => 'Payroll',
    ],

    'rotations' => [
        'route' => 'rotations.index',
        'icon' => 'bi bi-arrow-repeat',
        'label' => 'Rotacje',
    ],

    'employee_rates' => [
        'route' => 'employee-rates.index',
        'icon' => 'bi bi-currency-dollar',
        'label' => 'Stawki pracowników',
    ],

    'advances' => [
        'route' => 'advances.index',
        'icon' => 'bi bi-wallet2',
        'label' => 'Zaliczki',
    ],

    'documents' => [
        'route' => 'documents.index',
        'icon' => 'bi bi-file-earmark-text',
        'label' => 'Wymagania formalne',
    ],

    'employee_documents' => [
        'route' => 'employee-documents.index',
        'icon' => 'bi bi-file-earmark-medical',
        'label' => 'Dokumenty pracowników',
    ],

    'users' => [
        'route' => 'users.index',
        'icon' => 'bi bi-person-gear',
        'label' => 'Użytkownicy',
    ],

    'user_roles' => [
        'route' => 'user-roles.index',
        'icon' => 'bi bi-shield-check',
        'label' => 'Role i uprawnienia',
    ],

    'system_actions' => [
        'route' => 'system-actions.index',
        'icon' => 'bi bi-gear',
        'label' => 'Akcje systemowe',
    ],

    'audit_logs' => [
        'route' => 'audit-logs.index',
        'icon' => 'bi bi-journal-text',
        'label' => 'Logi systemowe',
    ],

    'system_changelog' => [
        'route' => 'changelog.index',
        'icon' => 'bi bi-megaphone',
        'label' => 'Dziennik zmian',
        // To samo co logi — jawne, bo mapowanie trasy → uprawnienie bywa `changelog.view` zamiast `audit-logs.view`.
        'permission' => 'audit-logs.view',
    ],

    'prompt_engine' => [
        'route' => 'prompts.index',
        'icon' => 'bi bi-braces',
        'label' => 'Prompt engine',
        'permission' => 'tasks.view',
    ],

    'mine_projects' => [
        'route' => 'mine.projects.index',
        'icon' => 'bi bi-folder',
        'label' => 'Projekty zespołu',
    ],

    'mine_time_logs' => [
        'route' => 'mine.time-logs.monthly-grid',
        'icon' => 'bi bi-clock',
        'label' => 'Ewidencja godzin',
    ],

    'mine_employees' => [
        'route' => 'mine.employees.index',
        'icon' => 'bi bi-people',
        'label' => 'Pracownicy zespołu',
    ],

    'mine_assignments' => [
        'route' => 'mine.assignments.index',
        'icon' => 'bi bi-person-check',
        'label' => 'Przypisania zespołu',
    ],

    'mine_tasks' => [
        'route' => 'mine.tasks.index',
        'icon' => 'bi bi-list-check',
        'label' => 'Moje zadania',
    ],

    'mine_employee_evaluations' => [
        'route' => 'mine.employee-evaluations.index',
        'icon' => 'bi bi-star',
        'label' => 'Oceny pracowników',
    ],
];
