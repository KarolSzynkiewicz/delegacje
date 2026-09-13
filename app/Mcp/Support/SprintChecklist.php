<?php

namespace App\Mcp\Support;

use App\Models\Sprint;
use App\Models\SprintDodItem;
use App\Models\SprintMilestone;
use App\Models\SprintReadinessItem;
use App\Support\EntityLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SprintChecklist
{
    public const READY = 'ready';

    public const DONE = 'done';

    public const MILESTONE = 'milestone';

    /**
     * @return self::READY|self::DONE|self::MILESTONE|null
     */
    public static function normalize(?string $list): ?string
    {
        return match (strtolower(trim((string) $list))) {
            'ready', 'readiness', 'start', 'dor' => self::READY,
            'done', 'dod' => self::DONE,
            'milestone', 'milestones', 'kamien', 'kamień' => self::MILESTONE,
            default => null,
        };
    }

    public static function label(string $list): string
    {
        return match ($list) {
            self::READY => 'warunek startu',
            self::DONE => 'warunek ukończenia',
            self::MILESTONE => 'kamień milowy',
            default => 'pozycja',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function payload(Sprint $sprint): array
    {
        $lists = $sprint->checklists();

        return [
            'id' => $sprint->id,
            'name' => $sprint->name,
            'url' => EntityLinks::sprint($sprint),
            'ready' => $lists['readiness'],
            'done' => $lists['done'],
            'milestones' => $lists['milestones'],
        ];
    }

    public static function relation(Sprint $sprint, string $list): HasMany
    {
        return match ($list) {
            self::READY => $sprint->readinessItems(),
            self::DONE => $sprint->doneItems(),
            self::MILESTONE => $sprint->milestones(),
            default => $sprint->readinessItems(),
        };
    }

    public static function findItem(Sprint $sprint, string $list, int $itemId): SprintReadinessItem|SprintDodItem|SprintMilestone|null
    {
        return self::relation($sprint, $list)->whereKey($itemId)->first();
    }

    /**
     * @return array{id: int, list: string, name: string, done: bool, due_date?: string|null}
     */
    public static function itemPayload(Model $item, string $list): array
    {
        $done = method_exists($item, 'isCompleted')
            ? $item->isCompleted()
            : $item->completed_at !== null;

        $payload = [
            'id' => (int) $item->getKey(),
            'list' => $list,
            'name' => (string) $item->name,
            'done' => $done,
        ];

        if ($item instanceof SprintMilestone) {
            $payload['due_date'] = $item->due_date?->toDateString();
        }

        return $payload;
    }
}
