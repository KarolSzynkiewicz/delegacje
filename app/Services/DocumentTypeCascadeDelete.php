<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentTypeCascadeDelete
{
    /**
     * Kasuje typ i jego wpisy. Plik znika tylko wtedy, gdy żaden inny wpis nie trzyma tej ścieżki.
     *
     * @return array{entries: int, files: int, name: string}
     */
    public function delete(Document $document): array
    {
        $paths = EmployeeDocument::query()
            ->where('document_id', $document->id)
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->filter()
            ->unique()
            ->values();

        $entries = EmployeeDocument::query()->where('document_id', $document->id)->count();
        $name = $document->name;

        DB::transaction(function () use ($document) {
            EmployeeDocument::query()->where('document_id', $document->id)->delete();
            $document->delete();
        });

        $files = 0;
        $disk = Storage::disk('public');

        foreach ($paths as $path) {
            if (EmployeeDocument::query()->where('file_path', $path)->exists()) {
                continue;
            }

            if ($disk->exists($path) && $disk->delete($path)) {
                $files++;
            }
        }

        return [
            'entries' => $entries,
            'files' => $files,
            'name' => $name,
        ];
    }
}
