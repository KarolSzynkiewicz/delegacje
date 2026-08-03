<?php

namespace App\Livewire;

use App\Services\MbsLeadImportService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class MbsLeadImport extends Component
{
    use WithFileUploads;

    public bool $show = false;

    #[Validate('required|file|mimes:csv,txt|max:2048')]
    public $csvFile = null;

    /** Plain array — Livewire can't serialize Collection as a public property. */
    public array $preview = [];

    /** @var array<string, string|null> */
    public array $detectedHeaders = [];

    public ?string $parseError = null;

    public ?array $importResult = null;

    // ─────────────────────────────────────────────────────────────────────────

    public function openModal(): void
    {
        $this->reset(['csvFile', 'preview', 'detectedHeaders', 'parseError', 'importResult']);
        $this->show = true;
    }

    public function closeModal(): void
    {
        $this->show = false;
        $this->reset(['csvFile', 'preview', 'detectedHeaders', 'parseError', 'importResult']);
    }

    public function updatedCsvFile(): void
    {
        $this->preview          = [];
        $this->detectedHeaders  = [];
        $this->parseError       = null;
        $this->importResult     = null;

        $this->validateOnly('csvFile');

        $content = file_get_contents($this->csvFile->getRealPath());

        if ($content === false) {
            $this->parseError = 'Nie można odczytać pliku.';
            return;
        }

        try {
            $result                = app(MbsLeadImportService::class)->preview($content);
            $this->preview         = $result['rows']->values()->all();
            $this->detectedHeaders = $result['detected_headers'];
        } catch (\RuntimeException $e) {
            $this->parseError = $e->getMessage();
            $this->preview    = [];
        }
    }

    public function doImport(): void
    {
        if (empty($this->preview)) {
            return;
        }

        try {
            $this->importResult = app(MbsLeadImportService::class)->import(collect($this->preview));
        } catch (\Exception $e) {
            $this->parseError = 'Błąd importu: '.$e->getMessage();
            return;
        }

        $this->preview = [];
        // Use reset() so Livewire properly handles the TemporaryUploadedFile lifecycle
        // instead of forcing it to null (which triggers a toJSON serialization error).
        $this->reset('csvFile');

        $this->dispatch('leads-imported');
    }

    public function render()
    {
        return view('livewire.mbs-lead-import');
    }
}
