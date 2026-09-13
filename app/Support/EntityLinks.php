<?php

namespace App\Support;

use App\Models\ForumPost;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\WorkItem;

class EntityLinks
{
    public static function task(ProjectTask|int $task): string
    {
        return route('tasks.show', $task);
    }

    public static function post(ForumPost|int $post): string
    {
        return route('dashboard.posts.show', $post);
    }

    public static function sprint(Sprint|int $sprint): string
    {
        return route('sprints.show', $sprint);
    }

    public static function workItem(WorkItem $item): string
    {
        return $item->openUrl();
    }
}
