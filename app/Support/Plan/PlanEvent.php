<?php

namespace App\Support\Plan;

use Carbon\CarbonImmutable;

final class PlanEvent
{
    /**
     * @param  list<array{id: int, title: string, url: string, typeLabel: string, typeIcon: string, dueLabel: ?string, dueLate: bool}>  $members
     */
    public function __construct(
        public readonly string $key,
        public readonly string $kind,
        public readonly ?int $workItemId,
        public readonly ?int $blockId,
        public readonly string $title,
        public readonly string $url,
        public readonly CarbonImmutable $startsAt,
        public readonly CarbonImmutable $endsAt,
        public readonly string $day,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly string $typeIcon,
        public readonly bool $ghost,
        public readonly bool $movable,
        public readonly bool $allDay = false,
        public readonly bool $isSession = false,
        public readonly array $members = [],
        public int $lane = 0,
        public int $laneCount = 1,
        public float $topPercent = 0,
        public float $heightPercent = 8,
    ) {}

    public function timeLabel(): string
    {
        if ($this->allDay) {
            return 'cały dzień';
        }

        return $this->startsAt->format('H:i').'–'.$this->endsAt->format('H:i');
    }

    public function durationMinutes(): int
    {
        return max(0, (int) round(($this->endsAt->getTimestamp() - $this->startsAt->getTimestamp()) / 60));
    }

    public function isCompact(): bool
    {
        return ! $this->allDay && $this->durationMinutes() <= 15;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'key' => $this->key,
            'kind' => $this->kind,
            'workItemId' => $this->workItemId,
            'blockId' => $this->blockId,
            'title' => $this->title,
            'url' => $this->url,
            'typeLabel' => $this->typeLabel,
            'typeIcon' => $this->typeIcon,
            'timeLabel' => $this->timeLabel(),
            'allDay' => $this->allDay,
            'ghost' => $this->ghost,
            'isSession' => $this->isSession,
            'memberCount' => count($this->members),
            'members' => $this->members,
        ];
    }
}
