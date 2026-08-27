<?php

namespace App\Services\Llm;

use App\Enums\ProcedureSubjectType;
use App\Exceptions\LlmException;
use App\Support\Llm\PromptContext;

/**
 * Proponuje przepływ procedury (SOP) na podstawie metadanych z modalu „Nowa procedura”.
 *
 * Model zwraca wyłącznie płaską listę kroków. Topologię grafu (start, koniec,
 * krawędzie, pozycje, ikony) buduje PHP — dzięki temu wynik zawsze otwiera się
 * poprawnie w edytorze, niezależnie od tego co wymyśli model.
 */
class ProcedureFlowSuggestionService extends StructuredSuggestionService
{
    public const STEP_TYPES = ['task', 'checklist', 'decision', 'wait'];

    /** Kolory i ikony muszą zgadzać się z NODE_TYPES w public/js/procedure-editor.js. */
    private const NODE_STYLE = [
        'start' => ['icon' => '▶', 'color' => '#3ecf8e'],
        'end' => ['icon' => '⏹', 'color' => '#ef5a6f'],
        'task' => ['icon' => '☰', 'color' => '#5b8def'],
        'checklist' => ['icon' => '☑', 'color' => '#3ecf8e'],
        'decision' => ['icon' => '◆', 'color' => '#f0a84e'],
        'wait' => ['icon' => '⏱', 'color' => '#8b96b3'],
    ];

    private const WAIT_UNITS = ['sek', 'min', 'godz', 'dni'];

    /**
     * @param  array{name: string, category?: ?string, subject_type?: ?string, description?: ?string}  $input
     * @return list<array{type: string, name: string, description: string, instructions: string, checklist: list<string>, options: list<string>, wait: array{duration: int, unit: string}|null}>
     */
    /**
     * Przykładowy JSON do wklejenia z zewnętrznego chatu (ChatGPT itd.).
     *
     * @return array<string, mixed>
     */
    public static function importFormatExample(): array
    {
        return [
            'steps' => [
                [
                    'type' => 'task',
                    'name' => 'Przygotuj dokumenty',
                    'description' => 'Po co ten krok',
                    'instructions' => 'Co dokładnie zrobić',
                ],
                [
                    'type' => 'checklist',
                    'name' => 'Sprawdź komplet',
                    'checklist' => ['Umowa', 'Badania', 'Szkolenie BHP'],
                ],
                [
                    'type' => 'decision',
                    'name' => 'Czy wszystko kompletne?',
                    'options' => ['Tak', 'Nie'],
                ],
                [
                    'type' => 'wait',
                    'name' => 'Czekaj na akceptację',
                    'wait' => ['duration' => 2, 'unit' => 'dni'],
                ],
            ],
        ];
    }

    /**
     * Parsuje wklejony tekst (JSON) do znormalizowanych kroków — ten sam format co odpowiedź modelu.
     *
     * @return list<array{type: string, name: string, description: string, instructions: string, checklist: list<string>, options: list<string>, wait: array{duration: int, unit: string}|null}>
     *
     * @throws LlmException
     */
    public function importStepsFromText(string $text, int $maxSteps = 8): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new LlmException('Wklej tekst z krokami procedury.');
        }

        $data = $this->decodeJson($text);
        $steps = $this->normalizeSteps($data['steps'] ?? $data, $maxSteps);

        if ($steps === []) {
            throw new LlmException('Nie znaleziono kroków. Użyj JSON z tablicą steps (patrz przykład formatu).');
        }

        return $steps;
    }

    public function suggest(array $input, int $maxSteps = 8): array
    {
        $subjectLabel = ProcedureSubjectType::tryFrom((string) ($input['subject_type'] ?? ''))?->label();

        $context = PromptContext::make()
            ->field('Nazwa procedury', $input['name'] ?? null, 200)
            ->field('Kategoria', $input['category'] ?? null, 100)
            ->field('Procedura dotyczy encji', $subjectLabel, 100)
            ->field('Opis', $input['description'] ?? null, 1000);

        $data = $this->askForJson($context, $this->systemPrompt($maxSteps), maxTokens: 2048);

        $steps = $this->normalizeSteps($data['steps'] ?? $data, $maxSteps);

        if ($steps === []) {
            throw new LlmException('Model nie zaproponował żadnych kroków procedury.');
        }

        return $steps;
    }

    private function systemPrompt(int $maxSteps): string
    {
        return implode(' ', [
            'Jesteś projektantem procedur (SOP).',
            'Na podstawie metadanych zaprojektuj kolejne kroki procedury.',
            'Odpowiedz TYLKO JSON w formacie:',
            '{"steps":[{"type":"task","name":"Nazwa kroku","description":"Po co ten krok","instructions":"Co dokładnie zrobić",'
                .'"checklist":["punkt 1"],"options":["Tak","Nie"],"wait":{"duration":5,"unit":"min"}}]}.',
            'Dozwolone type: task (zwykły krok), checklist (krok z listą punktów), decision (rozgałęzienie), wait (oczekiwanie).',
            'Pole checklist podawaj tylko dla type=checklist, options tylko dla type=decision (2-4 opcje), wait tylko dla type=wait (unit: sek|min|godz|dni).',
            'Nie dodawaj kroków start ani koniec — dokłada je aplikacja.',
            'Po polsku, max '.$maxSteps.' kroków, name max 80 znaków, description max 200 znaków, instructions max 400 znaków.',
        ]);
    }

    /**
     * @return list<array{type: string, name: string, description: string, instructions: string, checklist: list<string>, options: list<string>, wait: array{duration: int, unit: string}|null}>
     */
    private function normalizeSteps(mixed $items, int $maxSteps): array
    {
        if (! is_array($items)) {
            return [];
        }

        $steps = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['name' => $item];
            }

            if (! is_array($item)) {
                continue;
            }

            $name = $this->cleanLine($item['name'] ?? null, 80);

            if ($name === '') {
                continue;
            }

            $type = is_string($item['type'] ?? null) && in_array($item['type'], self::STEP_TYPES, true)
                ? $item['type']
                : 'task';

            $checklist = $type === 'checklist'
                ? $this->stringList($item['checklist'] ?? [], 10, 120)
                : [];

            $options = $type === 'decision'
                ? array_slice($this->stringList($item['options'] ?? [], 4, 60), 0, 4)
                : [];

            if ($type === 'decision' && count($options) < 2) {
                $options = ['Tak', 'Nie'];
            }

            $steps[] = [
                'type' => $type,
                'name' => $name,
                'description' => $this->cleanLine($item['description'] ?? null, 200),
                'instructions' => $this->cleanLine($item['instructions'] ?? null, 400),
                'checklist' => $checklist,
                'options' => $options,
                'wait' => $type === 'wait' ? $this->normalizeWait($item['wait'] ?? null) : null,
            ];

            if (count($steps) >= $maxSteps) {
                break;
            }
        }

        return $steps;
    }

    /** @return array{duration: int, unit: string} */
    private function normalizeWait(mixed $wait): array
    {
        $duration = is_array($wait) ? (int) ($wait['duration'] ?? 0) : 0;
        $unit = is_array($wait) && is_string($wait['unit'] ?? null) ? $wait['unit'] : 'min';

        return [
            'duration' => max(1, min(999, $duration ?: 5)),
            'unit' => in_array($unit, self::WAIT_UNITS, true) ? $unit : 'min',
        ];
    }

    /**
     * Składa kroki w graf zgodny z edytorem: start → kroki → koniec.
     *
     * Krok decyzyjny prowadzi pierwszą opcją dalej, a pozostałymi do końca —
     * to tylko punkt wyjścia, użytkownik przepina gałęzie w edytorze.
     *
     * @param  list<array<string, mixed>>  $steps
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    public function buildDefinition(array $steps): array
    {
        $steps = array_values($steps);
        $spacing = 220;
        $baseY = 220;

        $nodes = [$this->node('start-1', 'start', 'Start', 60, $baseY)];
        $ids = [];

        foreach ($steps as $index => $step) {
            $id = 'step-'.($index + 1);
            $ids[$index] = $id;

            $node = $this->node(
                $id,
                (string) $step['type'],
                (string) $step['name'],
                60 + $spacing * ($index + 1),
                $baseY,
            );

            $node['description'] = (string) ($step['description'] ?? '');
            $node['instructions'] = (string) ($step['instructions'] ?? '');

            if ($node['type'] === 'checklist') {
                $node['checklist'] = $this->checklistItems($step['checklist'] ?? [], $id);
            }

            if ($node['type'] === 'decision') {
                $node['decision'] = [
                    'mode' => count($step['options'] ?? []) > 2 ? 'multi' : 'yesno',
                    'options' => $this->decisionOptions($step['options'] ?? [], $id),
                ];
            }

            if ($node['type'] === 'wait') {
                $node['wait'] = $step['wait'] ?? ['duration' => 5, 'unit' => 'min'];
            }

            $nodes[] = $node;
        }

        $endId = 'end-1';
        $nodes[] = $this->node($endId, 'end', 'Koniec', 60 + $spacing * (count($steps) + 1), $baseY);

        $edges = [];
        $previous = 'start-1';

        // Po decyzji krawędź wejściową kolejnego kroku tworzy już wybrana opcja.
        $needsIncomingEdge = true;

        foreach ($steps as $index => $step) {
            $current = $ids[$index];
            $next = $ids[$index + 1] ?? $endId;

            if ($needsIncomingEdge) {
                $edges[] = $this->edge($previous, $current);
            }

            $previous = $current;
            $needsIncomingEdge = true;

            if (($step['type'] ?? null) === 'decision') {
                foreach ($nodes[$index + 1]['decision']['options'] as $position => $option) {
                    $edges[] = $this->edge(
                        $current,
                        $position === 0 ? $next : $endId,
                        $option['label'],
                        $option['id'],
                    );
                }

                $needsIncomingEdge = false;
            }
        }

        if ($steps === []) {
            $edges[] = $this->edge('start-1', $endId);
        } elseif (($steps[array_key_last($steps)]['type'] ?? null) !== 'decision') {
            $edges[] = $this->edge($ids[array_key_last($steps)], $endId);
        }

        return [
            'nodes' => $nodes,
            'edges' => $this->uniqueEdges($edges),
        ];
    }

    /** @return array<string, mixed> */
    private function node(string $id, string $type, string $name, int $x, int $y): array
    {
        $style = self::NODE_STYLE[$type] ?? self::NODE_STYLE['task'];

        return [
            'id' => $id,
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'name' => $name,
            'description' => '',
            'instructions' => '',
            'estimatedDuration' => null,
            'durationUnit' => 'min',
            'icon' => $style['icon'],
            'color' => $style['color'],
            'required' => false,
            'assigned_user_id' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function edge(string $from, string $to, string $label = '', ?string $optionId = null): array
    {
        return [
            'id' => 'edge-'.$from.'-'.$to.($optionId ? '-'.$optionId : ''),
            'from' => $from,
            'to' => $to,
            'label' => $label,
            'condition' => '',
            'optionId' => $optionId,
        ];
    }

    /**
     * @param  list<string>  $items
     * @return list<array<string, mixed>>
     */
    private function checklistItems(array $items, string $nodeId): array
    {
        return array_values(array_map(fn (string $title, int $order) => [
            'id' => $nodeId.'-chk-'.($order + 1),
            'title' => $title,
            'description' => '',
            'optional' => false,
            'order' => $order,
        ], $items, array_keys($items)));
    }

    /**
     * @param  list<string>  $options
     * @return list<array<string, mixed>>
     */
    private function decisionOptions(array $options, string $nodeId): array
    {
        $options = $options === [] ? ['Tak', 'Nie'] : $options;

        return array_values(array_map(fn (string $label, int $index) => [
            'id' => $nodeId.'-opt-'.($index + 1),
            'label' => $label,
        ], $options, array_keys($options)));
    }

    /**
     * @param  list<array<string, mixed>>  $edges
     * @return list<array<string, mixed>>
     */
    private function uniqueEdges(array $edges): array
    {
        $seen = [];
        $unique = [];

        foreach ($edges as $edge) {
            if (isset($seen[$edge['id']])) {
                continue;
            }

            $seen[$edge['id']] = true;
            $unique[] = $edge;
        }

        return $unique;
    }
}
