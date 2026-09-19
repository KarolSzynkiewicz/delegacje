<?php

namespace App\Enums;

enum NotificationEvent: string
{
    case TaskAssigned = 'task_assigned';
    case CommentMentioned = 'comment_mentioned';
    case TaskCommentAdded = 'task_comment_added';
    case ApprovalRequested = 'approval_requested';
    case ApprovalDecided = 'approval_decided';
    case MentionCompleted = 'mention_completed';
    case CommentLiked = 'comment_liked';
    case ProcedureWaitElapsed = 'procedure_wait_elapsed';
    case ProcedureStepReady = 'procedure_step_ready';
    case MeetingInvited = 'meeting_invited';
    case SubtaskMentioned = 'subtask_mentioned';

    public function label(): string
    {
        return match ($this) {
            self::TaskAssigned => 'Ktoś dodał mi zadanie',
            self::CommentMentioned => 'Ktoś wspomniał mnie w komentarzu',
            self::TaskCommentAdded => 'Komentarz na moim zadaniu',
            self::ApprovalRequested => 'Prośba o zatwierdzenie',
            self::ApprovalDecided => 'Decyzja w moim wniosku',
            self::MentionCompleted => 'Ukończono wzmiankę, którą zleciłem',
            self::CommentLiked => 'Polubienie komentarza',
            self::ProcedureWaitElapsed => 'Oczekiwanie w procedurze minęło',
            self::ProcedureStepReady => 'Mój krok w procedurze',
            self::MeetingInvited => 'Spotkanie ze mną',
            self::SubtaskMentioned => 'Wzmianka w podzadaniu',
        };
    }

    public function group(): NotificationEventGroup
    {
        return match ($this) {
            self::CommentMentioned, self::CommentLiked, self::SubtaskMentioned => NotificationEventGroup::Social,
            self::ProcedureWaitElapsed, self::ProcedureStepReady => NotificationEventGroup::Procedure,
            default => NotificationEventGroup::Work,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TaskAssigned => 'bi-person-check-fill text-primary',
            self::ApprovalRequested => 'bi-check2-circle text-warning',
            self::ApprovalDecided => 'bi-clipboard-check text-success',
            self::MentionCompleted => 'bi-check2-square-fill text-success',
            self::CommentMentioned, self::SubtaskMentioned => 'bi-chat-quote-fill text-info',
            self::TaskCommentAdded => 'bi-chat-left-text-fill text-success',
            self::CommentLiked => 'bi-heart-fill text-danger',
            self::ProcedureWaitElapsed => 'bi-hourglass-split text-warning',
            self::ProcedureStepReady => 'bi-share-fill text-primary',
            self::MeetingInvited => 'bi-calendar-event-fill text-info',
        };
    }

    public function appearsInPreferences(): bool
    {
        return $this !== self::SubtaskMentioned;
    }

    public function defaultEnabled(NotificationChannel $channel): bool
    {
        return match ($channel) {
            NotificationChannel::Database => true,
            NotificationChannel::Mail => in_array($this, [
                self::TaskAssigned,
                self::CommentMentioned,
                self::ApprovalRequested,
                self::ApprovalDecided,
                self::ProcedureStepReady,
                self::MeetingInvited,
            ], true),
            NotificationChannel::Push => in_array($this, [
                self::ApprovalRequested,
                self::MeetingInvited,
            ], true),
            NotificationChannel::WhatsApp, NotificationChannel::Sms => false,
        };
    }

    /**
     * @return list<self>
     */
    public static function preferenceRows(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event) => $event->appearsInPreferences(),
        ));
    }
}
