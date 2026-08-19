<?php

namespace App\Providers;

use App\Events\ProcedureRunStepCompleted;
use App\Events\ProcedureRunStepEntered;
use App\Listeners\CompleteProcedureStepMentionTasks;
use App\Listeners\NotifyProcedureStepAssignee;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ProcedureRunStepEntered::class => [
            NotifyProcedureStepAssignee::class,
        ],
        ProcedureRunStepCompleted::class => [
            CompleteProcedureStepMentionTasks::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
