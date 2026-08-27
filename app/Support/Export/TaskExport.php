<?php

namespace App\Support\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TaskExport
{
    public const LIMIT = 200;

    /**
     * @param  Builder|iterable<mixed>  $query
     */
    public static function csv(Builder|iterable $query): StreamedResponse
    {
        if ($query instanceof Builder) {
            $query = (clone $query)->with(['assignedTo'])->limit(self::LIMIT);
        }

        return Csv::streamDownload(
            $query,
            ['ID', 'Nazwa', 'Status', 'Priorytet', 'Osoba', 'Kategoria', 'Termin'],
            fn (mixed $item) => self::row($item),
            'zadania-filtr-'.now()->format('Y-m-d').'.csv',
        );
    }

    /**
     * @return list<int|string>
     */
    private static function row(mixed $item): array
    {
        $status = $item->status ?? '';
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        $category = $item->category ?? '';
        if (is_object($category)) {
            $category = $category->name ?? '';
        }

        $due = $item->due_at ?? $item->due_date ?? null;
        if ($due instanceof \DateTimeInterface) {
            $due = $due->format('Y-m-d');
        }

        return [
            $item->id,
            (string) ($item->title ?? $item->name ?? ''),
            (string) $status,
            $item->priority ?? '',
            $item->assignedTo?->name ?? '',
            (string) $category,
            $due === null ? '' : (string) $due,
        ];
    }
}
