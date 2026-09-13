<?php

namespace App\Services;

use App\Models\Sprint;
use App\Models\SprintDodItem;
use App\Models\SprintMilestone;
use App\Models\SprintReadinessItem;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SprintCreationService
{
    private const DEDUPE_WINDOW_SECONDS = 600;

    /**
     * @param  array{
     *     name: string,
     *     goal?: string|null,
     *     definition_of_done?: string|array<int, string>|null,
     *     definition_of_ready?: array<int, string>|null,
     *     readiness_items?: array<int, string>|null,
     *     done_items?: array<int, string>|null,
     *     milestones?: array<int, array<string, mixed>>|null,
     *     start_date: string,
     *     end_date: string,
     * }  $data
     */
    public function create(array $data, User $creator): Sprint
    {
        $name = trim((string) $data['name']);
        $startDate = (string) $data['start_date'];
        $endDate = (string) $data['end_date'];
        $lockKey = sprintf(
            'sprint-create:%d:%s:%s:%s',
            $creator->id,
            mb_strtolower($name),
            $startDate,
            $endDate,
        );

        return Cache::lock($lockKey, 10)->block(5, function () use ($data, $creator, $name, $startDate, $endDate) {
            $existing = Sprint::query()
                ->where('created_by', $creator->id)
                ->where('name', $name)
                ->whereDate('start_date', $startDate)
                ->whereDate('end_date', $endDate)
                ->where('created_at', '>=', now()->subSeconds(self::DEDUPE_WINDOW_SECONDS))
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $sprint = Sprint::query()->create([
                'name' => $name,
                'goal' => $this->nullableTrim($data['goal'] ?? null),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_by' => $creator->id,
            ]);

            $this->seedChecklists($sprint, $data, $creator);

            return $sprint;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function seedChecklists(Sprint $sprint, array $data, User $creator): void
    {
        foreach ($this->lines($data['definition_of_ready'] ?? $data['readiness_items'] ?? null) as $index => $name) {
            SprintReadinessItem::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'position' => $index,
                'created_by' => $creator->id,
            ]);
        }

        foreach ($this->lines($data['definition_of_done'] ?? $data['done_items'] ?? null) as $index => $name) {
            SprintDodItem::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'position' => $index,
                'created_by' => $creator->id,
            ]);
        }

        $milestones = $data['milestones'] ?? null;
        if (! is_array($milestones)) {
            return;
        }

        foreach (array_values($milestones) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = $this->nullableTrim($row['name'] ?? null);
            if ($name === null) {
                continue;
            }

            SprintMilestone::query()->create([
                'sprint_id' => $sprint->id,
                'name' => $name,
                'due_date' => $row['due_date'] ?? $sprint->end_date?->toDateString(),
                'position' => $index,
                'created_by' => $creator->id,
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function lines(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($line) => $this->nullableTrim($line))
                ->filter()
                ->values()
                ->all();
        }

        $text = $this->nullableTrim($value);
        if ($text === null) {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n/', $text) ?: [];

        return collect($parts)
            ->map(function (string $line) {
                $trimmed = trim($line);
                $trimmed = preg_replace('/^[-*•]\s+/u', '', $trimmed) ?? $trimmed;

                return $this->nullableTrim($trimmed);
            })
            ->filter()
            ->values()
            ->all();
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
