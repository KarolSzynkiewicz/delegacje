<?php

namespace App\Providers;

use App\Events\ApprovalAssigneeChanged;
use App\Events\ApprovalDecisionRecorded;
use App\Events\CommentPosted;
use App\Events\CommentWasLiked;
use App\Events\MeetingParticipantsChanged;
use App\Events\MentionWasCompleted;
use App\Events\ProcedureRunStepCompleted;
use App\Events\ProcedureRunStepEntered;
use App\Events\ProcedureWaitFinished;
use App\Events\TaskAssigneeChanged;
use App\Listeners\CompleteProcedureStepMentionTasks;
use App\Listeners\NotifyProcedureStepAssignee;
use App\Listeners\SendApprovalDecidedNotification;
use App\Listeners\SendApprovalRequestedNotification;
use App\Listeners\SendCommentLikedNotification;
use App\Listeners\SendCommentNotifications;
use App\Listeners\SendMeetingInvitedNotification;
use App\Listeners\SendMentionCompletedNotification;
use App\Listeners\SendProcedureWaitElapsedNotification;
use App\Listeners\SendTaskAssignedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        TaskAssigneeChanged::class => [
            SendTaskAssignedNotification::class,
        ],
        CommentPosted::class => [
            SendCommentNotifications::class,
        ],
        CommentWasLiked::class => [
            SendCommentLikedNotification::class,
        ],
        ApprovalAssigneeChanged::class => [
            SendApprovalRequestedNotification::class,
        ],
        ApprovalDecisionRecorded::class => [
            SendApprovalDecidedNotification::class,
        ],
        MentionWasCompleted::class => [
            SendMentionCompletedNotification::class,
        ],
        ProcedureWaitFinished::class => [
            SendProcedureWaitElapsedNotification::class,
        ],
        MeetingParticipantsChanged::class => [
            SendMeetingInvitedNotification::class,
        ],
        ProcedureRunStepEntered::class => [
            NotifyProcedureStepAssignee::class,
        ],
        ProcedureRunStepCompleted::class => [
            CompleteProcedureStepMentionTasks::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
