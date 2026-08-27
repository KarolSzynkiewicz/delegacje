<?php

namespace App\Support\Export;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class Csv
{
    /**
     * @param  Builder|iterable<mixed>  $rows
     * @param  list<string>  $headers
     * @param  callable(mixed): list<mixed>  $mapper
     */
    public static function streamDownload(Builder|iterable $rows, array $headers, callable $mapper, string $filename): StreamedResponse
    {
        if ($rows instanceof Builder) {
            $rows = $rows->cursor();
        }

        return response()->streamDownload(function () use ($rows, $headers, $mapper) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, $mapper($row), ';');
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
