<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Location::class => \App\Policies\LocationPolicy::class,
        \App\Models\Vehicle::class => \App\Policies\VehiclePolicy::class,
        \App\Models\Accommodation::class => \App\Policies\AccommodationPolicy::class,
        \Spatie\Permission\Models\Role::class => \App\Policies\UserRolePolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\LogisticsEvent::class => \App\Policies\LogisticsEventPolicy::class,
        \App\Models\Equipment::class => \App\Policies\EquipmentPolicy::class,
        \App\Models\EquipmentIssue::class => \App\Policies\EquipmentIssuePolicy::class,
        \App\Models\TransportCost::class => \App\Policies\TransportCostPolicy::class,
        \App\Models\TimeLog::class => \App\Policies\TimeLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
