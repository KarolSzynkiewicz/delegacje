<?php

namespace App\Mcp\Support;

use App\Models\User;
use App\Support\Plan\PlanEvent;
use Carbon\CarbonInterface;

final class PlanPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function event(PlanEvent $event): array
    {
        return [
            'key' => $event->key,
            'kind' => $event->kind,
            'block_id' => $event->blockId,
            'work_item_id' => $event->workItemId,
            'title' => $event->title,
            'url' => $event->url,
            'day' => $event->day,
            'starts_at' => $event->startsAt->toIso8601String(),
            'ends_at' => $event->endsAt->toIso8601String(),
            'time_label' => $event->timeLabel(),
            'all_day' => $event->allDay,
            'ghost' => $event->ghost,
            'is_session' => $event->isSession,
            'type' => $event->type,
            'type_label' => $event->typeLabel,
            'members' => $event->members,
        ];
    }

    /**
     * @return array{id: int, name: string}
     */
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public static function planUrl(User $calendarUser, CarbonInterface $weekStart): string
    {
        return route('work-items.plan', [
            'w' => $weekStart->toDateString(),
            'u' => $calendarUser->id,
        ]);
    }
}
