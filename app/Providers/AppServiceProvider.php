<?php

namespace App\Providers;

use App\Livewire\Pulse\UserRouteUsage;
use App\Livewire\Pulse\UserRouteVisits;
use App\Models\AccommodationAssignment;
use App\Models\Adjustment;
use App\Models\ApprovalRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Employee;
use App\Models\LogisticsEvent;
use App\Models\LogisticsEventParticipant;
use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Models\ProjectAssignment;
use App\Models\ProjectTask;
use App\Models\RecruitmentCandidate;
use App\Models\Sprint;
use App\Models\SprintMilestone;
use App\Models\TaskSubtask;
use App\Models\TransportCost;
use App\Models\User;
use App\Models\VehicleAssignment;
use App\Models\WarehouseDispatch;
use App\Observers\AuditableModelObserver;
use App\Observers\NotifiesApprovalAssignee;
use App\Observers\ProcedureTemplateObserver;
use App\Observers\SyncsWorkItems;
use App\Services\SystemBootstrapService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Employee::observe(AuditableModelObserver::class);
        LogisticsEvent::observe(AuditableModelObserver::class);
        VehicleAssignment::observe(AuditableModelObserver::class);
        ProjectAssignment::observe(AuditableModelObserver::class);
        AccommodationAssignment::observe(AuditableModelObserver::class);
        TransportCost::observe(AuditableModelObserver::class);
        Adjustment::observe(AuditableModelObserver::class);
        LogisticsEventParticipant::observe(AuditableModelObserver::class);
        RecruitmentCandidate::observe(AuditableModelObserver::class);
        ProjectTask::observe(AuditableModelObserver::class);
        TaskSubtask::observe(AuditableModelObserver::class);
        Comment::observe(AuditableModelObserver::class);
        Sprint::observe(AuditableModelObserver::class);
        SprintMilestone::observe(AuditableModelObserver::class);
        Attachment::observe(AuditableModelObserver::class);

        ProcedureTemplate::observe(ProcedureTemplateObserver::class);

        ProjectTask::observe(SyncsWorkItems::class);
        TaskSubtask::observe(SyncsWorkItems::class);
        ProcedureRun::observe(SyncsWorkItems::class);
        WarehouseDispatch::observe(SyncsWorkItems::class);
        CommentMention::observe(SyncsWorkItems::class);
        ApprovalRequest::observe(SyncsWorkItems::class);
        ApprovalRequest::observe(NotifiesApprovalAssignee::class);

        Relation::enforceMorphMap([
            'project_assignment' => \App\Models\ProjectAssignment::class,
            'vehicle_assignment' => \App\Models\VehicleAssignment::class,
            'accommodation_assignment' => \App\Models\AccommodationAssignment::class,
            'rotation' => \App\Models\Rotation::class,
            'user' => \App\Models\User::class, // Required for Spatie Permission morphedByMany
            // Commentable models
            'project' => \App\Models\Project::class,
            'project_task' => \App\Models\ProjectTask::class,
            'vehicle' => \App\Models\Vehicle::class,
            'accommodation' => \App\Models\Accommodation::class,
            'logistics_event' => \App\Models\LogisticsEvent::class,
            'location' => \App\Models\Location::class,
            'employee' => \App\Models\Employee::class,
            'recruitment_process' => \App\Models\RecruitmentProcess::class,
            'recruitment_candidate' => \App\Models\RecruitmentCandidate::class,
            'warehouse_dispatch' => \App\Models\WarehouseDispatch::class,
            'comment' => \App\Models\Comment::class,
            'comment_mention' => \App\Models\CommentMention::class,
            'task_subtask' => \App\Models\TaskSubtask::class,
            'sprint' => \App\Models\Sprint::class,
            'procedure_run' => \App\Models\ProcedureRun::class,
            'work_item' => \App\Models\WorkItem::class,
            'approval_request' => \App\Models\ApprovalRequest::class,
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

        Gate::define('viewPulse', function (?User $user) {
            return $user instanceof User && $user->isAdmin();
        });

        Livewire::component('pulse.user-route-visits', UserRouteVisits::class);
        Livewire::component('pulse.user-route-usage', UserRouteUsage::class);
    }
}
