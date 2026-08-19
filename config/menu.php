<?php

return [
    'weekly_overview',
    'tasks',
    'procedures',

    [
        'label' => 'Mój zespół',
        'icon' => 'bi bi-people-fill',
        'items' => [
            'mine_projects',
            'mine_tasks',
            'mine_time_logs',
            'mine_employees',
            'mine_assignments',
            'mine_employee_evaluations',
        ],
    ],

    [
        'label' => 'Zasoby',
        'icon' => 'bi bi-boxes',
        'items' => [
            'projects',
            'vehicles',
            'accommodations',
            'companies',
        ],
    ],

    [
        'label' => 'Historia',
        'icon' => 'bi bi-clock-history',
        'items' => [
            'assignments',
            'vehicle_assignments',
            'accommodation_assignments',
            'company_assignments',
            'demands',
            'vehicle_repairs',
        ],
    ],

    [
        'label' => 'Logistyka',
        'icon' => 'bi bi-tools',
        'items' => [
            'locations',
            'warehouses',
            'rotations',
            'return_trips',
            'departures',
            'transfers',
        ],
    ],

    [
        'label' => 'Finanse',
        'icon' => 'bi bi-cash-stack',
        'items' => [
            'dashboard',
            'fixed_costs',
            'project_variable_costs',
            'transport_costs',
        ],
    ],

    [
        'label' => 'Kadry',
        'icon' => 'bi bi-briefcase',
        'items' => [
            'employees',
            'recruitment_applications',
            'roles',
            'documents',
            'employee_documents',
        ],
    ],

    [
        'label' => 'Rozliczenia',
        'icon' => 'bi bi-calculator',
        'items' => [
            'payrolls',
            'time_logs',
            'adjustments',
            'advances',
            'employee_rates',
        ],
    ],

    [
        'label' => 'Administracja',
        'icon' => 'bi bi-shield-lock',
        'items' => [
            'users',
            'user_roles',
            'system_actions',
            'pulse',
            'audit_logs',
            'system_changelog',
            'prompt_engine',
        ],
    ],
];
