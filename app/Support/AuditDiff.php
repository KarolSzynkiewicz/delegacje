<?php

namespace App\Support;

use App\Enums\LogisticsEventStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use Illuminate\Support\Str;

/**
 * Buduje wiersze porównania „przed / po” dla logów audytu (bez surowego JSON).
 */
final class AuditDiff
{
    /**
     * @return array<int, array{key: string, label: string, before: string, after: string, kind: string}>
     */
    public static function rows(?array $oldValues, ?array $newValues, string $event): array
    {
        $oldFlat = self::flatten($oldValues ?? []);
        $newFlat = self::flatten($newValues ?? []);

        $allKeys = array_unique(array_merge(array_keys($oldFlat), array_keys($newFlat)));
        natcasesort($allKeys);
        $allKeys = array_values($allKeys);

        $rows = [];
        foreach ($allKeys as $key) {
            $inOld = array_key_exists($key, $oldFlat);
            $inNew = array_key_exists($key, $newFlat);

            $beforeRaw = $inOld ? $oldFlat[$key] : null;
            $afterRaw = $inNew ? $newFlat[$key] : null;

            $beforeStr = self::formatValue($beforeRaw);
            $afterStr = self::formatValue($afterRaw);

            $beforeDisplay = self::prettifyFieldValue($key, $beforeRaw, $beforeStr);
            $afterDisplay = self::prettifyFieldValue($key, $afterRaw, $afterStr);

            $kind = match (true) {
                ! $inOld && $inNew => 'added',
                $inOld && ! $inNew => 'removed',
                $beforeStr === $afterStr => 'unchanged',
                default => 'changed',
            };

            if ($event === 'updated' && $kind === 'unchanged') {
                continue;
            }

            $rows[] = [
                'key' => $key,
                'label' => self::labelForKey($key),
                'before' => $inOld ? $beforeDisplay : '—',
                'after' => $inNew ? $afterDisplay : '—',
                'kind' => $kind,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function flatten(array $data, string $prefix = ''): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                if ($value === []) {
                    $out[$path] = [];

                    continue;
                }
                $isAssoc = array_keys($value) !== range(0, count($value) - 1);
                if ($isAssoc) {
                    $out = array_merge($out, self::flatten($value, $path));
                } else {
                    $out[$path] = $value;
                }
            } else {
                $out[$path] = $value;
            }
        }

        return $out;
    }

    private static function labelForKey(string $key): string
    {
        $labels = config('audit.field_labels', []);
        if (isset($labels[$key])) {
            return $labels[$key];
        }

        $last = Str::afterLast($key, '.');

        return Str::title(str_replace('_', ' ', $last));
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($value === '') {
            return '(pusty)';
        }

        if (is_bool($value)) {
            return $value ? 'tak' : 'nie';
        }

        if (is_array($value)) {
            $json = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: '[]';

            return mb_strlen($json) > 200 ? mb_substr($json, 0, 197).'…' : $json;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        $s = (string) $value;

        return mb_strlen($s) > 500 ? mb_substr($s, 0, 497).'…' : $s;
    }

    /**
     * Czytelne etykiety dla typowych pól enum (status) zamiast surowych wartości z bazy.
     */
    private static function prettifyFieldValue(string $key, mixed $raw, string $fallback): string
    {
        $baseKey = Str::afterLast($key, '.');
        if ($baseKey !== 'status' || (! is_string($raw) && $raw !== null)) {
            return $fallback;
        }

        $v = is_string($raw) ? $raw : null;
        if ($v === null || $v === '') {
            return $fallback;
        }

        foreach ([LogisticsEventStatus::class, ProjectStatus::class, TaskStatus::class] as $enumClass) {
            $case = $enumClass::tryFrom($v);
            if ($case !== null) {
                return $case->label();
            }
        }

        return $fallback;
    }
}
