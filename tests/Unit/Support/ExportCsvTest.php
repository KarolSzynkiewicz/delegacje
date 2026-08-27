<?php

namespace Tests\Unit\Support;

use App\Enums\TaskStatus;
use App\Support\Export\Csv;
use App\Support\Export\TaskExport;
use Carbon\Carbon;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    public function test_csv_writes_bom_semicolon_headers_and_mapped_rows(): void
    {
        $response = Csv::streamDownload(
            [
                (object) ['id' => 7, 'name' => 'Alfa'],
                (object) ['id' => 8, 'name' => 'Beta'],
            ],
            ['ID', 'Nazwa'],
            fn ($row) => [$row->id, $row->name],
            'demo.csv',
        );

        $csv = $this->streamedBody($response);

        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame(
            "ID;Nazwa\n7;Alfa\n8;Beta\n",
            substr($csv, 3),
        );
    }

    public function test_task_export_csv_is_flat_excel_pl_and_maps_work_item_fields(): void
    {
        $this->travelTo(Carbon::parse('2026-08-27 12:00:00'));

        $response = TaskExport::csv([
            (object) [
                'id' => 11,
                'title' => 'DR do Berlina',
                'name' => 'ignored',
                'status' => TaskStatus::IN_PROGRESS,
                'priority' => 2,
                'assignedTo' => (object) ['name' => 'Karol'],
                'category' => 'AI / Sprint',
                'due_at' => Carbon::parse('2026-09-01'),
                'due_date' => null,
            ],
        ]);

        $csv = $this->streamedBody($response);

        $this->assertStringContainsString('filename=zadania-filtr-2026-08-27.csv', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame(
            "ID;Nazwa;Status;Priorytet;Osoba;Kategoria;Termin\n11;DR do Berlina;in_progress;2;Karol;AI / Sprint;2026-09-01\n",
            substr($csv, 3),
        );
    }

    private function streamedBody(\Symfony\Component\HttpFoundation\StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
