<?php

namespace App\Providers;

use App\Services\SystemBootstrapService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

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
        // Force debug mode if enabled via cache (for production debugging)
        if (\Illuminate\Support\Facades\Cache::get('force_debug_mode', false)) {
            config(['app.debug' => true]);
        }

        // Force HTTPS for all URLs in production (Railway uses HTTPS)
        if (config('app.env') === 'production' || request()->isSecure()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Auto-configure secure session cookies for HTTPS
        // This is critical for Railway where APP_URL is HTTPS but SESSION_SECURE_COOKIE might not be set
        if (config('app.env') === 'production' || str_starts_with(config('app.url', ''), 'https://')) {
            config(['session.secure' => true]);
        }

        // Custom morph map for assignment models and User (for Spatie Permission)
        // This ensures polymorphic assignments only point to valid assignment models
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
            'logistics_event' => \App\Models\LogisticsEvent::class,
            'location' => \App\Models\Location::class,
            // Future assignments (e.g., EquipmentAssignment) must be added here
        ]);

        // Auto-bootstrap system if uninitialized (only in non-production)
        // This is a state check, not a user action - no HTTP endpoint exposed
        // System transitions from "uninitialized" to "initialized" state automatically
        if (config('app.env') !== 'production') {
            try {
                $this->app->make(SystemBootstrapService::class)->ensureInitialized();
            } catch (\Exception $e) {
                // Silently fail - system might not be ready yet (DB not migrated)
                // Will retry on next request
            }
        }
    }
}
