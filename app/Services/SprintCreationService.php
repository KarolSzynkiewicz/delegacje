<?php

namespace App\Services;

use App\Models\Sprint;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SprintCreationService
{
    private const DEDUPE_WINDOW_SECONDS = 600;

    /**
     * @param  array{
     *     name: string,
     *     goal?: string|null,
     *     definition_of_done?: string|null,
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

            return Sprint::query()->create([
                'name' => $name,
                'goal' => $this->nullableTrim($data['goal'] ?? null),
                'definition_of_done' => $this->nullableTrim($data['definition_of_done'] ?? null),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_by' => $creator->id,
            ]);
        });
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
