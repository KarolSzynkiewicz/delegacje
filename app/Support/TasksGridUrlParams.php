<?php

namespace App\Support;

class TasksGridUrlParams
{
    /** @var list<string> */
    public const KEYS = [
        'view',
        'searchTask',
        'searchCategory',
        'searchAssignedTo',
        'status',
        'assignedFilter',
        'types',
        'groupBy',
        'sortField',
        'sortDirection',
    ];

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, string|list<string>>
     */
    public static function normalize(array $params): array
    {
        $normalized = [];

        foreach (self::KEYS as $key) {
            if (! array_key_exists($key, $params)) {
                continue;
            }

            $value = $params[$key];

            if ($key === 'types') {
                $types = is_array($value)
                    ? array_values(array_unique(array_filter($value, fn ($v) => $v !== '' && $v !== null)))
                    : [];
                sort($types);

                if ($types === []) {
                    continue;
                }

                $normalized[$key] = $types;

                continue;
            }

            if ($value === null || $value === '' || $value === false) {
                continue;
            }

            if ($key === 'sortField' && (string) $value === 'created_at') {
                continue;
            }

            if ($key === 'sortDirection' && (string) $value === 'desc') {
                continue;
            }

            $normalized[$key] = (string) $value;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    public static function matches(array $a, array $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }

    /**
     * @param  array<string, mixed>  $requestQuery
     * @return array<string, string>
     */
    public static function fromRequestQuery(array $requestQuery): array
    {
        return self::normalize(array_intersect_key($requestQuery, array_flip(self::KEYS)));
    }
}
