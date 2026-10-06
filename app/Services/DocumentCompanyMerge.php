<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EmployeeDocument;
use App\Support\PublicFileLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DocumentCompanyMerge
{
    public const BATCH = 10;

    public function __construct(private PublicFileLink $files) {}

    /**
     * @param  array<int, int>  $sourceCompanyMap  document_id => company_id
     * @return array<string, mixed>
     */
    public function start(int $targetId, array $sourceCompanyMap): array
    {
        $target = Document::query()->findOrFail($targetId);
        $marked = false;

        if (! $target->is_company_scoped) {
            $target->is_company_scoped = true;
            $target->save();
            $marked = true;
        }

        $items = [];

        foreach ($sourceCompanyMap as $documentId => $companyId) {
            $ids = EmployeeDocument::query()
                ->where('document_id', (int) $documentId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($ids as $id) {
                $items[] = ['id' => (int) $id, 'company_id' => (int) $companyId];
            }
        }

        $token = (string) Str::uuid();
        $state = [
            'target_id' => $target->id,
            'items' => $items,
            'cursor' => 0,
            'copied' => 0,
            'skipped' => 0,
            'missing_files' => 0,
            'linked' => 0,
            'byte_copies' => 0,
            'target_marked' => $marked,
        ];

        Cache::put($this->key($token), $state, now()->addHours(6));

        return $this->payload($token, $state);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function advance(string $token, int $batch = self::BATCH): ?array
    {
        $state = Cache::get($this->key($token));

        if (! is_array($state)) {
            return null;
        }

        $items = $state['items'];
        $slice = array_slice($items, (int) $state['cursor'], $batch);

        foreach ($slice as $item) {
            $result = $this->copyOne((int) $state['target_id'], (int) $item['id'], (int) $item['company_id']);
            $state['cursor']++;
            $state['copied'] += $result['copied'];
            $state['skipped'] += $result['skipped'];
            $state['missing_files'] += $result['missing_files'];
            $state['linked'] += $result['linked'];
            $state['byte_copies'] += $result['byte_copies'];
        }

        Cache::put($this->key($token), $state, now()->addHours(6));

        return $this->payload($token, $state);
    }

    /**
     * @return array{copied: int, skipped: int, missing_files: int, linked: int, byte_copies: int}
     */
    private function copyOne(int $targetId, int $sourceId, int $companyId): array
    {
        $empty = ['copied' => 0, 'skipped' => 1, 'missing_files' => 0, 'linked' => 0, 'byte_copies' => 0];
        $source = EmployeeDocument::query()->find($sourceId);

        if (! $source || EmployeeDocument::query()->where('copied_from_id', $source->id)->exists()) {
            return $empty;
        }

        $file = $this->files->duplicate($source->file_path, 'employee_documents/'.$source->employee_id);

        EmployeeDocument::query()->create([
            'document_id' => $targetId,
            'company_id' => $companyId,
            'employee_id' => $source->employee_id,
            'copied_from_id' => $source->id,
            'valid_from' => $source->valid_from,
            'valid_to' => $source->valid_to,
            'kind' => $source->kind,
            'notes' => $source->notes,
            'file_path' => $file['path'],
        ]);

        return [
            'copied' => 1,
            'skipped' => 0,
            'missing_files' => $file['mode'] === 'missing' ? 1 : 0,
            'linked' => $file['mode'] === 'link' ? 1 : 0,
            'byte_copies' => $file['mode'] === 'copy' ? 1 : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function payload(string $token, array $state): array
    {
        $total = count($state['items']);

        return [
            'token' => $token,
            'total' => $total,
            'done' => (int) $state['cursor'],
            'copied' => (int) $state['copied'],
            'skipped' => (int) $state['skipped'],
            'missing_files' => (int) $state['missing_files'],
            'linked' => (int) $state['linked'],
            'byte_copies' => (int) $state['byte_copies'],
            'target_marked' => (bool) $state['target_marked'],
            'finished' => (int) $state['cursor'] >= $total,
        ];
    }

    private function key(string $token): string
    {
        return 'document-merge:'.$token;
    }
}
