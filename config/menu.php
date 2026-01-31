<?php

return [
    'dashboard',
    'weekly_overview',
    'tasks',
    
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
        ],
    ],
    
    [
        'label' => 'Historia',
        'icon' => 'bi bi-clock-history',
        'items' => [
            'assignments',
            'vehicle_assignments',
            'accommodation_assignments',
            'demands',

        ],
    ],
    
    [
        'label' => 'Logistyka',
        'icon' => 'bi bi-truck',
        'items' => [
            'locations',
            'equipment',
            'equipment_issues',
            'return_trips',
            'departures',
        ],
    ],
    
    [
        'label' => 'Koszty',
        'icon' => 'bi bi-cash-stack',
        'items' => [
            'project_variable_costs',
            'transport_costs',
            'fixed_costs',
        ],
    ],
    
    [
        'label' => 'Kadry',
        'icon' => 'bi bi-briefcase',
        'items' => [
            'employees',
            'roles',
            'adjustments',
            'time_logs',
            'payrolls',
            'rotations',
            'employee_rates',
            'advances',
            'documents',
            'employee_documents',
        ],
    ],
    
    [
        'label' => 'Administracja',
        'icon' => 'bi bi-shield-lock',
        'items' => [
            'users',
            'user_roles',
        ],
    ],
];
