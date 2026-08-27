<?php

namespace App\Services\Llm;

use App\Exceptions\LlmException;
use App\Support\EdiTaskEdit;
use App\Support\Llm\PromptContext;

/**
 * Edi: propozycje zmian pól na istniejących taskach z eksportu filtra.
 * Nic nie zapisuje — PHP odcina pola spoza editable i no-opy.
 */
class TasksFilterMutateService extends StructuredSuggestionService
{
    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $editable
     * @param  list<string>  $filterLabels
     * @return list<array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}>
     */
    public function propose(array $records, array $editable, string $intent, array $filterLabels): array
    {
        if ($records === [] || $editable === []) {
            return [];
        }

        $context = PromptContext::make()
            ->list('Aktywne filtry', $filterLabels, 160, 20)
            ->list('Pola, które wolno zmienić', $editable, 40, 10)
            ->records('Zadania (tylko te id)', $records, 40, array_values(array_unique(array_merge(['id', 'name'], $editable))));

        $data = $this->askForJson(
            $context,
            $this->systemPrompt($intent, $editable),
            maxTokens: 2048,
            temperature: 0.1,
        );

        return $this->diffsFromChanges($records, $data['changes'] ?? [], $editable);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  mixed  $changes
     * @param  list<string>  $editable
     * @return list<array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}>
     */
    public function diffsFromChanges(array $records, mixed $changes, array $editable): array
    {
        if (! is_array($changes)) {
            return [];
        }

        $byId = [];
        $bySource = [];
        foreach ($records as $record) {
            $id = isset($record['id']) && is_numeric($record['id']) ? (int) $record['id'] : 0;
            if ($id > 0) {
                $byId[$id] = $record;
            }
            $sourceId = isset($record['source_id']) && is_numeric($record['source_id']) ? (int) $record['source_id'] : 0;
            if ($sourceId > 0) {
                $bySource[$sourceId] = $record;
            }
        }

        $diffs = [];
        $seen = [];

        foreach ($changes as $change) {
            if (! is_array($change)) {
                continue;
            }

            $id = isset($change['id']) && is_numeric($change['id']) ? (int) $change['id'] : 0;
            $field = is_string($change['field'] ?? null) ? $change['field'] : '';
            $record = $id > 0 ? $this->recordForId($byId, $bySource, $id) : null;

            if ($record === null || ! in_array($field, $editable, true)) {
                continue;
            }

            $rowId = (int) $record['id'];
            $key = $rowId.'.'.$field;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $from = $record[$field] ?? null;
            $to = $this->normalizeProposed($field, $change['value'] ?? null);
            $kind = EdiTaskEdit::kind($from, $to);

            if ($kind === null) {
                continue;
            }

            $diffs[] = [
                'row_id' => $rowId,
                'field' => $field,
                'kind' => $kind,
                'from' => $from,
                'to' => $to,
                'from_label' => EdiTaskEdit::label($field, $from),
                'to_label' => EdiTaskEdit::label($field, $to),
            ];
        }

        return $diffs;
    }

    /**
     * Paczka do ChatGPT: instrukcja + snapshot + aktualne diffs (po ręcznej korekcie).
     *
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $editable
     * @param  list<array{row_id?: int, field?: string, to?: mixed}>  $diffs
     * @param  list<string>  $filterLabels
     * @return array<string, mixed>
     */
    public function exportPayload(array $records, array $editable, array $diffs = [], array $filterLabels = []): array
    {
        $byId = [];
        foreach ($records as $record) {
            $id = isset($record['id']) && is_numeric($record['id']) ? (int) $record['id'] : 0;
            if ($id > 0) {
                $byId[$id] = $record;
            }
        }

        $changes = [];
        foreach ($diffs as $diff) {
            if (! is_array($diff)) {
                continue;
            }
            $id = (int) ($diff['row_id'] ?? 0);
            $field = is_string($diff['field'] ?? null) ? $diff['field'] : '';
            if ($id < 1 || ! in_array($field, $editable, true)) {
                continue;
            }
            $value = $diff['to'] ?? null;
            $changes[] = [
                'id' => $id,
                'field' => $field,
                'value' => $value,
            ];
            if (isset($byId[$id])) {
                $byId[$id][$field] = $value;
            }
        }

        return [
            'format' => 'edi-task-edit',
            'version' => 1,
            'instruction' => EdiTaskEdit::chatInstruction($editable),
            'editable' => array_values($editable),
            'filters' => $filterLabels,
            'count' => count($byId),
            'tasks' => array_values($byId),
            'changes' => $changes,
        ];
    }

    /**
     * JSON z ChatGPT / eksportu Impki: {changes:[]} albo {tasks:[{id, name, ...}]}.
     *
     * @param  list<array<string, mixed>>  $records
     * @param  list<string>  $editable
     * @return list<array{row_id: int, field: string, kind: string, from: mixed, to: mixed, from_label: string, to_label: string}>
     */
    public function parseImportedJson(string $text, array $records, array $editable): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new LlmException('Wklej JSON z changes[] albo tasks[].');
        }

        try {
            $data = $this->decodeJson($text);
        } catch (LlmException) {
            throw new LlmException('Niepoprawny JSON. Wklej obiekt z tablicą changes albo tasks.');
        }

        $changes = $data['changes'] ?? null;

        if (! is_array($changes) || $changes === []) {
            $tasks = $data['tasks'] ?? null;
            if (! is_array($tasks) && array_is_list($data)) {
                $tasks = $data;
            }
            if (! is_array($tasks) || $tasks === []) {
                throw new LlmException('JSON musi mieć tablicę changes albo tasks.');
            }
            $changes = $this->changesFromProposedTasks($tasks, $editable);
        }

        $diffs = $this->diffsFromChanges($records, $changes, $editable);

        if ($diffs === []) {
            throw new LlmException('W JSON nie ma żadnej zmiany względem aktualnego stanu filtra.');
        }

        return $diffs;
    }

    /**
     * @param  list<mixed>  $tasks
     * @param  list<string>  $editable
     * @return list<array{id: int, field: string, value: mixed}>
     */
    protected function changesFromProposedTasks(array $tasks, array $editable): array
    {
        $changes = [];

        foreach ($tasks as $task) {
            if (! is_array($task)) {
                continue;
            }
            $id = isset($task['id']) && is_numeric($task['id'])
                ? (int) $task['id']
                : (isset($task['source_id']) && is_numeric($task['source_id']) ? (int) $task['source_id'] : 0);
            if ($id < 1) {
                continue;
            }
            foreach ($editable as $field) {
                if (! array_key_exists($field, $task)) {
                    continue;
                }
                $changes[] = [
                    'id' => $id,
                    'field' => $field,
                    'value' => $task[$field],
                ];
            }
        }

        return $changes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byId
     * @param  array<int, array<string, mixed>>  $bySource
     * @return array<string, mixed>|null
     */
    protected function recordForId(array $byId, array $bySource, int $id): ?array
    {
        return $byId[$id] ?? $bySource[$id] ?? null;
    }

    /**
     * @param  list<string>  $editable
     */
    protected function systemPrompt(string $intent, array $editable): string
    {
        $focus = $intent === 'mutate-category'
            ? 'Uzupełnij brakujące kategorie i ujednolić oczywisty bałagan. Nie zmieniaj kategorii, która już jest sensowna. Max 40 zmian.'
            : 'Popraw nazwy, opisy, kategorie, priorytety i terminy tam, gdzie treść jest pusta, bezsensowna albo niespójna. Nie wymyślaj faktów spoza listy. Max 40 zmian.';

        return EdiTaskEdit::chatInstruction($editable)."\n".$focus;
    }

    public function normalizeProposed(string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($field === 'priority') {
            if ($value === '' || $value === false) {
                return null;
            }
            if (! is_numeric($value)) {
                return null;
            }
            $priority = (int) $value;

            return in_array($priority, [1, 2, 3, 4, 5], true) ? $priority : null;
        }

        if ($field === 'due_date') {
            $raw = trim((string) $value);
            if ($raw === '') {
                return null;
            }
            try {
                return \Carbon\Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                return null;
            }
        }

        $text = $this->cleanLine(is_scalar($value) ? (string) $value : null, $field === 'description' ? 4000 : 255);

        return $text === '' ? null : $text;
    }
}
