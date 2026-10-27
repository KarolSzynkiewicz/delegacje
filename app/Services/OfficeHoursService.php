<?php

namespace App\Services;

use App\Enums\OfficeWorkLocation;
use App\Models\OfficeProfile;
use App\Models\OfficeWorkDay;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfficeHoursService
{
    public const STANDARD_DAY_HOURS = 8.0;

    /**
     * @return array{
     *     monthStart: Carbon,
     *     monthEnd: Carbon,
     *     rangeStart: Carbon,
     *     rangeEnd: Carbon,
     *     days: list<Carbon>,
     *     profiles: Collection<int, OfficeProfile>,
     *     cells: array<string, array{starts_at: string, ends_at: string, hours: float, location: string}>
     * }
     */
    public function getMonthlyGrid(string $month, ?int $onlyUserId = null): array
    {
        $monthStart = Carbon::parse($month.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        return $this->getGridForRange($monthStart, $monthEnd, $onlyUserId);
    }

    /**
     * @return array{
     *     monthStart: Carbon,
     *     monthEnd: Carbon,
     *     rangeStart: Carbon,
     *     rangeEnd: Carbon,
     *     days: list<Carbon>,
     *     profiles: Collection<int, OfficeProfile>,
     *     cells: array<string, array{starts_at: string, ends_at: string, hours: float, location: string}>
     * }
     */
    public function getGridForRange(Carbon $rangeStart, Carbon $rangeEnd, ?int $onlyUserId = null): array
    {
        $rangeStart = $rangeStart->copy()->startOfDay();
        $rangeEnd = $rangeEnd->copy()->startOfDay();
        $monthStart = $rangeStart->copy()->startOfMonth();
        $monthEnd = $rangeEnd->copy()->endOfMonth();

        $days = [];
        for ($d = $rangeStart->copy(); $d->lte($rangeEnd); $d->addDay()) {
            $days[] = $d->copy();
        }

        $profilesQuery = OfficeProfile::query()
            ->with('user:id,name,email')
            ->active()
            ->where(function ($q) use ($rangeEnd) {
                $q->whereNull('started_on')
                    ->orWhereDate('started_on', '<=', $rangeEnd);
            })
            ->where(function ($q) use ($rangeStart) {
                $q->whereNull('ended_on')
                    ->orWhereDate('ended_on', '>=', $rangeStart);
            })
            ->orderBy(
                \App\Models\User::select('name')
                    ->whereColumn('users.id', 'office_profiles.user_id')
                    ->limit(1)
            );

        if ($onlyUserId) {
            $profilesQuery->where('user_id', $onlyUserId);
        }

        $profiles = $profilesQuery->get();
        $profileIds = $profiles->pluck('id')->all();

        $cells = [];
        if ($profileIds !== []) {
            $workDays = OfficeWorkDay::query()
                ->whereIn('office_profile_id', $profileIds)
                ->whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
                ->get();

            foreach ($workDays as $day) {
                $key = $this->cellKey((int) $day->office_profile_id, $day->work_date->format('Y-m-d'));
                $cells[$key] = [
                    'starts_at' => $this->formatTimeForInput($day->starts_at),
                    'ends_at' => $this->formatTimeForInput($day->ends_at),
                    'hours' => (float) $day->hours,
                    'location' => $day->location instanceof OfficeWorkLocation
                        ? $day->location->value
                        : (string) $day->location,
                ];
            }
        }

        return compact('monthStart', 'monthEnd', 'rangeStart', 'rangeEnd', 'days', 'profiles', 'cells');
    }

    /**
     * @param  array<string, array{starts_at?: mixed, ends_at?: mixed, location?: mixed}>  $cells
     */
    public function saveMonthCells(array $cells, ?int $allowedProfileId = null): int
    {
        $saved = 0;
        $errors = [];
        $fteByProfile = OfficeProfile::query()
            ->whereIn('id', $this->profileIdsFromKeys(array_keys($cells)))
            ->pluck('fte', 'id');

        $pending = [];

        foreach ($cells as $key => $payload) {
            try {
                [$profileId, $date] = $this->parseCellKey((string) $key);
            } catch (ValidationException $e) {
                $errors = array_merge($errors, $e->errors());

                continue;
            }

            if ($allowedProfileId !== null && $profileId !== $allowedProfileId) {
                $errors['cells'][] = 'Brak uprawnień do edycji tego profilu.';

                continue;
            }

            $location = $this->normalizeLocation($payload['location'] ?? null);
            $isLeave = $location === OfficeWorkLocation::Leave->value;
            $startAttr = "cells.{$key}.starts_at";
            $endAttr = "cells.{$key}.ends_at";

            if ($isLeave) {
                $fte = (float) ($fteByProfile[$profileId] ?? 1);
                $pending[] = [
                    'profile_id' => $profileId,
                    'date' => $date,
                    'starts_at' => null,
                    'ends_at' => null,
                    'hours' => round(self::STANDARD_DAY_HOURS * $fte, 2),
                    'location' => $location,
                    'delete' => false,
                ];

                continue;
            }

            $startsAt = $this->normalizeTime($payload['starts_at'] ?? null, $startAttr, $errors);
            $endsAt = $this->normalizeTime($payload['ends_at'] ?? null, $endAttr, $errors);

            if ($startsAt === null && $endsAt === null) {
                $pending[] = [
                    'profile_id' => $profileId,
                    'date' => $date,
                    'delete' => true,
                ];

                continue;
            }

            if ($startsAt === null || $endsAt === null) {
                if ($startsAt === null && ($payload['starts_at'] ?? '') === '') {
                    $errors[$startAttr][] = 'Podaj godzinę od.';
                }
                if ($endsAt === null && ($payload['ends_at'] ?? '') === '') {
                    $errors[$endAttr][] = 'Podaj godzinę do.';
                }

                continue;
            }

            if ($this->compareTimes($startsAt, $endsAt) >= 0) {
                $errors[$endAttr][] = 'Godzina „do” musi być późniejsza niż „od”.';

                continue;
            }

            $pending[] = [
                'profile_id' => $profileId,
                'date' => $date,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'hours' => $this->hoursBetween($startsAt, $endsAt),
                'location' => $location,
                'delete' => false,
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($pending, &$saved) {
            foreach ($pending as $row) {
                if ($row['delete']) {
                    $deleted = OfficeWorkDay::query()
                        ->where('office_profile_id', $row['profile_id'])
                        ->whereDate('work_date', $row['date'])
                        ->delete();
                    $saved += $deleted > 0 ? 1 : 0;

                    continue;
                }

                OfficeWorkDay::query()->updateOrCreate(
                    [
                        'office_profile_id' => $row['profile_id'],
                        'work_date' => $row['date'],
                    ],
                    [
                        'starts_at' => $row['starts_at'],
                        'ends_at' => $row['ends_at'],
                        'hours' => $row['hours'],
                        'location' => $row['location'],
                    ]
                );
                $saved++;
            }
        });

        return $saved;
    }

    public function cellKey(int $profileId, string $date): string
    {
        return $profileId.'_'.str_replace('-', '', $date);
    }

    public function hoursBetween(string $startsAt, string $endsAt): float
    {
        $start = Carbon::createFromFormat('H:i', $startsAt);
        $end = Carbon::createFromFormat('H:i', $endsAt);

        return round($start->diffInMinutes($end) / 60, 2);
    }

    /** @return int negative if a<b, 0 equal, positive if a>b */
    private function compareTimes(string $a, string $b): int
    {
        return Carbon::createFromFormat('H:i', $a)->timestamp
            <=> Carbon::createFromFormat('H:i', $b)->timestamp;
    }

    /**
     * Godziny komórki (urlop = 8 × FTE, inaczej z od–do).
     */
    public function resolveCellHours(?string $startsAt, ?string $endsAt, string $location, float $fte = 1.0): float
    {
        if ($location === OfficeWorkLocation::Leave->value) {
            return round(self::STANDARD_DAY_HOURS * $fte, 2);
        }

        if (! $startsAt || ! $endsAt) {
            return 0.0;
        }

        $errors = [];
        $from = $this->normalizeTime($startsAt, null, $errors);
        $to = $this->normalizeTime($endsAt, null, $errors);
        if ($from === null || $to === null || $this->compareTimes($from, $to) >= 0) {
            return 0.0;
        }

        return $this->hoursBetween($from, $to);
    }

    /**
     * Liczba dni roboczych (pn–pt) w zakresie włącznie.
     */
    public function weekdayCount(Carbon $from, Carbon $to): int
    {
        $count = 0;
        for ($d = $from->copy()->startOfDay(); $d->lte($to); $d->addDay()) {
            if (! $d->isWeekend()) {
                $count++;
            }
        }

        return $count;
    }

    public function monthNormHours(Carbon $monthStart, Carbon $monthEnd, float $fte): float
    {
        return round($this->weekdayCount($monthStart, $monthEnd) * self::STANDARD_DAY_HOURS * $fte, 2);
    }

    /**
     * @param  list<string|int>  $keys
     * @return list<int>
     */
    private function profileIdsFromKeys(array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            try {
                [$id] = $this->parseCellKey((string) $key);
                $ids[$id] = true;
            } catch (\Throwable) {
                // skip
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function parseCellKey(string $key): array
    {
        if (! preg_match('/^(\d+)_(\d{8})$/', $key, $m)) {
            throw ValidationException::withMessages([
                'cells' => 'Nieprawidłowy klucz komórki.',
            ]);
        }

        $ymd = $m[2];
        $date = substr($ymd, 0, 4).'-'.substr($ymd, 4, 2).'-'.substr($ymd, 6, 2);

        return [(int) $m[1], $date];
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function normalizeTime(mixed $time, ?string $attribute = null, array &$errors = []): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $raw = trim((string) $time);
        $raw = str_replace([',', '.'], ':', $raw);

        // 9 / 16 → 09:00 / 16:00
        if (preg_match('/^\d{1,2}$/', $raw)) {
            $h = (int) $raw;
            if ($h > 23) {
                if ($attribute) {
                    $errors[$attribute][] = 'Godzina musi być 0–23.';
                }

                return null;
            }

            return sprintf('%02d:00', $h);
        }

        // 800 / 0800 → 08:00
        if (preg_match('/^\d{3,4}$/', $raw)) {
            $raw = str_pad($raw, 4, '0', STR_PAD_LEFT);
            $raw = substr($raw, 0, 2).':'.substr($raw, 2, 2);
        }

        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $raw)) {
            $parts = explode(':', $raw);
            $raw = $parts[0].':'.$parts[1];
        }

        if (! preg_match('/^(\d{1,2}):(\d{1,2})$/', $raw, $m)) {
            if ($attribute) {
                $errors[$attribute][] = 'Nieprawidłowy format (np. 9, 900, 09:00).';
            }

            return null;
        }

        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($h > 23 || $min > 59) {
            if ($attribute) {
                $errors[$attribute][] = 'Nieprawidłowa godzina.';
            }

            return null;
        }

        return sprintf('%02d:%02d', $h, $min);
    }

    private function formatTimeForInput(mixed $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        if ($time instanceof Carbon) {
            return $time->format('H:i');
        }

        $raw = (string) $time;
        if (preg_match('/^\d{2}:\d{2}/', $raw, $m)) {
            return substr($m[0], 0, 5);
        }

        try {
            return Carbon::parse($raw)->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function normalizeLocation(mixed $location): string
    {
        $value = is_string($location) ? $location : OfficeWorkLocation::Office->value;
        $enum = OfficeWorkLocation::tryFrom($value);

        return ($enum ?? OfficeWorkLocation::Office)->value;
    }
}
