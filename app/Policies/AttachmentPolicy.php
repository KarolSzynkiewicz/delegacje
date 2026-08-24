<?php

namespace App\Policies;

use App\Enums\LogisticsEventType;
use App\Models\Accommodation;
use App\Models\ApprovalRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Employee;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\RecruitmentProcess;
use App\Models\Sprint;
use App\Models\User;
use App\Models\Vehicle;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $parent = $attachment->attachable;
        if ($parent instanceof ProjectTask) {
            return $user->hasPermission('tasks.view');
        }

        if ($parent instanceof ApprovalRequest) {
            return $user->can('view', $parent);
        }

        if ($parent instanceof Sprint) {
            return $user->hasPermission('tasks.view');
        }

        if ($parent instanceof Comment) {
            return $this->userCanAccessCommentContext($user, $parent);
        }

        return false;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $parent = $attachment->attachable;

        if ($parent instanceof ProjectTask) {
            return $user->hasPermission('tasks.update');
        }

        if ($parent instanceof ApprovalRequest) {
            return $parent->isCreator($user) || $parent->isApprover($user) || $user->hasPermission('tasks.update');
        }

        if ($parent instanceof Sprint) {
            return $user->hasPermission('tasks.update');
        }

        if ($parent instanceof Comment) {
            if ($attachment->uploaded_by === $user->id || $parent->user_id === $user->id) {
                return true;
            }

            return $user->isAdmin();
        }

        return false;
    }

    private function userCanAccessCommentContext(User $user, Comment $comment): bool
    {
        $ctx = $comment->commentable;
        if ($ctx instanceof ProjectTask) {
            return $user->hasPermission('tasks.view');
        }
        if ($ctx instanceof Sprint) {
            return $user->hasPermission('tasks.view');
        }
        if ($ctx instanceof Project) {
            return $user->hasPermission('projects.view');
        }
        if ($ctx instanceof Vehicle) {
            return $user->hasPermission('vehicles.view');
        }
        if ($ctx instanceof Accommodation) {
            return $user->hasPermission('accommodations.view');
        }
        if ($ctx instanceof Location) {
            return $user->hasPermission('locations.view');
        }
        if ($ctx instanceof Employee) {
            return $user->hasPermission('employees.view');
        }
        if ($ctx instanceof RecruitmentProcess) {
            return $user->hasPermission('recruitment-processes.view');
        }
        if ($ctx instanceof LogisticsEvent) {
            return match ($ctx->type) {
                LogisticsEventType::DEPARTURE => $user->hasPermission('departures.view'),
                LogisticsEventType::RETURN => $user->hasPermission('return-trips.view'),
                LogisticsEventType::TRANSFER => $user->hasPermission('transfers.view'),
                default => false,
            };
        }

        return false;
    }
}
