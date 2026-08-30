<?php

namespace App\Support;

use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;

class WorkItemListNavigator
{
    public const QUERY_KEY = 'wi';

    public static function sessionKey(): string
    {
        $userId = Auth::id();

        return 'work_item_list.'.($userId ?: 'guest');
    }

    /**
     * @param  list<int>  $ids
     */
    public static function remember(array $ids): void
    {
        if (! Auth::id()) {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        session([self::sessionKey() => $ids]);
    }

    public static function forget(): void
    {
        session()->forget(self::sessionKey());
    }

    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        $ids = session(self::sessionKey(), []);

        return is_array($ids) ? array_values(array_map('intval', $ids)) : [];
    }

    public static function itemUrl(WorkItem $item): string
    {
        return self::appendQuery($item->openUrl(), [self::QUERY_KEY => (string) $item->id]);
    }

    /**
     * @param  array<string, string>  $query
     */
    public static function appendQuery(string $url, array $query): string
    {
        $fragment = '';
        $hashPos = strpos($url, '#');
        if ($hashPos !== false) {
            $fragment = substr($url, $hashPos);
            $url = substr($url, 0, $hashPos);
        }

        $parts = parse_url($url) ?: [];
        $existing = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $existing);
        }

        $queryString = http_build_query(array_merge($existing, $query));
        $base = strtok($url, '?') ?: $url;

        return $base.($queryString !== '' ? '?'.$queryString : '').$fragment;
    }

    /**
     * @return array{index: int, total: int, prev: ?array{url: string, title: string}, next: ?array{url: string, title: string}}|null
     */
    public static function neighbors(?int $id): ?array
    {
        if (! $id) {
            return null;
        }

        $ids = self::ids();
        if (count($ids) < 2) {
            return null;
        }

        $index = array_search($id, $ids, true);
        if ($index === false) {
            return null;
        }

        $prevId = $ids[$index - 1] ?? null;
        $nextId = $ids[$index + 1] ?? null;
        $needed = array_values(array_filter([$prevId, $nextId]));
        $items = $needed === []
            ? collect()
            : WorkItem::query()->with('source')->whereIn('id', $needed)->get()->keyBy('id');

        $map = function (?int $neighborId) use ($items): ?array {
            if (! $neighborId) {
                return null;
            }
            $item = $items->get($neighborId);
            if (! $item instanceof WorkItem) {
                return null;
            }

            return [
                'url' => self::itemUrl($item),
                'title' => $item->title !== '' ? $item->title : $item->name,
            ];
        };

        return [
            'index' => $index + 1,
            'total' => count($ids),
            'prev' => $map($prevId),
            'next' => $map($nextId),
        ];
    }
}
