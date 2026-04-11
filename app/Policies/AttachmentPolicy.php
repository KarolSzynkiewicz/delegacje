<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;

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
            if (! $user->hasPermission('tasks.update')) {
                return false;
            }
            if (! $parent->project_id) {
                return true;
            }

            return $user->managesProject($parent->project_id);
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
        if ($ctx instanceof Project) {
            return $user->hasPermission('projects.view');
        }

        return $user->hasPermission('tasks.view') || $user->hasPermission('projects.view');
    }
}
