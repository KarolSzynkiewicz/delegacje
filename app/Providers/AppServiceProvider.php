<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom morph map for HasEmployee & HasDateRange implementations and User (for Spatie Permission)
        // This ensures polymorphic assignments only point to models implementing HasEmployee & HasDateRange
        // User is included because Spatie Permission uses morphedByMany for User model
        Relation::enforceMorphMap([
            'project_assignment' => \App\Models\ProjectAssignment::class,
            'vehicle_assignment' => \App\Models\VehicleAssignment::class,
            'accommodation_assignment' => \App\Models\AccommodationAssignment::class,
            'user' => \App\Models\User::class, // Required for Spatie Permission morphedByMany
            // Commentable models
            'project' => \App\Models\Project::class,
            'project_task' => \App\Models\ProjectTask::class,
            'vehicle' => \App\Models\Vehicle::class,
            'accommodation' => \App\Models\Accommodation::class,
            // Future assignments (e.g., EquipmentAssignment) must be added here
        ]);
    }
}
