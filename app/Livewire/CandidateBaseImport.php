<?php

namespace App\Livewire;

use App\Services\CandidateBaseImportService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class CandidateBaseImport extends Component
{
    use WithFileUploads;

    public bool $show = false;

    #[Validate('required|file|mimes:csv,txt|max:20480')]
    public $csvFile = null;

    /** Plain array — Livewire can't serialize Collection as a public property. */
    public array $preview = [];

    public ?string $parseError = null;

    public ?array $importResult = null;

    /** True while chunked import is in progress across Livewire requests. */
    public bool $importing = false;

    public int $importOffset = 0;

    public int $importTotal = 0;

    // ─────────────────────────────────────────────────────────────────────────

    public function openModal(): void
    {
        $this->reset(['csvFile', 'preview', 'parseError', 'importResult', 'importing', 'importOffset', 'importTotal']);
        $this->show = true;
    }

    public function closeModal(): void
    {
        // Don't allow closing mid-import — a half-written chunked run is confusing.
        if ($this->importing) {
            return;
        }

        $this->show = false;
        $this->reset(['csvFile', 'preview', 'parseError', 'importResult', 'importing', 'importOffset', 'importTotal']);
    }

    public function updatedCsvFile(): void
    {
        $this->preview = [];
        $this->parseError = null;
        $this->importResult = null;
        $this->importing = false;
        $this->importOffset = 0;
        $this->importTotal = 0;

        $this->validateOnly('csvFile');

        $content = file_get_contents($this->csvFile->getRealPath());

        if ($content === false) {
            $this->parseError = 'Nie można odczytać pliku.';

            return;
        }

        try {
            $service = app(CandidateBaseImportService::class);
            $rows = $service->parseOnly($content)['rows'];
            $this->preview = $service->preview($rows)->values()->all();
        } catch (\RuntimeException $e) {
            $this->parseError = $e->getMessage();
            $this->preview = [];
        }
    }

    public function doImport(): void
    {
        if (empty($this->preview) || $this->importing) {
            return;
        }

        $this->parseError = null;
        $this->importing = true;
        $this->importOffset = 0;
        $this->importTotal = count($this->preview);
        $this->importResult = [
            'created' => 0,
            'enriched' => 0,
            'skipped' => 0,
            'warnings' => [],
        ];

        $this->processImportChunk();
    }

    /**
     * One HTTP request = one DB chunk. Called from doImport and then via wire:poll
     * so Railway's proxy timeout never sees a multi-minute single request.
     */
    public function processImportChunk(): void
    {
        if (! $this->importing || empty($this->preview)) {
            return;
        }

        $chunkSize = CandidateBaseImportService::IMPORT_CHUNK_SIZE;
        $chunk = collect(array_slice($this->preview, $this->importOffset, $chunkSize));

        if ($chunk->isEmpty()) {
            $this->finishImport();

            return;
        }

        try {
            $stats = app(CandidateBaseImportService::class)->importChunk($chunk);
        } catch (\Exception $e) {
            $this->importing = false;
            $this->parseError = 'Błąd importu (przerwano po '.$this->importOffset.' z '.$this->importTotal.' wierszy): '.$e->getMessage();

            return;
        }

        $this->importResult['created'] += $stats['created'];
        $this->importResult['enriched'] += $stats['enriched'];
        $this->importResult['skipped'] += $stats['skipped'];
        $this->importResult['warnings'] = array_merge($this->importResult['warnings'], $stats['warnings']);
        $this->importOffset += $chunk->count();

        if ($this->importOffset >= $this->importTotal) {
            $this->finishImport();

            return;
        }

        // Chain the next chunk only after this request finishes — avoids wire:poll races
        // where two overlapping requests would re-process the same offset.
        $this->js('queueMicrotask(() => $wire.processImportChunk())');
    }

    private function finishImport(): void
    {
        $this->importing = false;
        $this->preview = [];
        // Use reset() so Livewire properly handles the TemporaryUploadedFile lifecycle
        // instead of forcing it to null (which triggers a toJSON serialization error).
        $this->reset('csvFile');

        $this->dispatch('candidates-imported');
    }

    public function render()
    {
        return view('livewire.candidate-base-import');
    }
}
