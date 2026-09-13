<?php

namespace App\Mcp\Support;

use App\Enums\ProcedureRunStatus;
use App\Enums\ProcedureSubjectType;
use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Support\EntityLinks;

final class ProcedurePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function template(ProcedureTemplate $template): array
    {
        $inProgress = (int) ($template->runs_in_progress_count ?? $template->runs()->where('status', ProcedureRunStatus::IN_PROGRESS)->count());
        $finished = (int) ($template->runs_finished_count ?? $template->runs()->where('status', ProcedureRunStatus::FINISHED)->count());
        $abandoned = (int) ($template->runs_abandoned_count ?? $template->runs()->where('status', ProcedureRunStatus::ABANDONED)->count());
        $subject = ProcedureSubjectType::tryFrom((string) $template->subject_type);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category,
            'description' => $template->description,
            'subject_type' => $template->subject_type,
            'subject_label' => $subject?->label(),
            'requires_subject' => $subject !== null,
            'node_count' => $template->nodeCount(),
            'runs' => [
                'in_progress' => $inProgress,
                'finished' => $finished,
                'abandoned' => $abandoned,
            ],
            'url' => route('procedure-templates.show', $template),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function runListItem(ProcedureRun $run): array
    {
        $run->loadMissing(['template:id,name', 'task:id,name,procedure_run_id', 'startedBy:id,name']);

        return [
            'id' => $run->id,
            'status' => $run->status?->value,
            'status_label' => $run->status?->label(),
            'needs_begin' => $run->needsBegin(),
            'template' => [
                'id' => $run->template?->id,
                'name' => $run->template?->name,
            ],
            'task' => $run->task ? [
                'id' => $run->task->id,
                'name' => $run->task->name,
                'url' => EntityLinks::task($run->task),
            ] : null,
            'started_by' => $run->startedBy?->name,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'current_steps' => collect($run->activeNodes())
                ->map(fn (array $node) => [
                    'node_id' => $node['id'] ?? null,
                    'name' => $node['name'] ?? ($node['type'] ?? 'krok'),
                    'type' => $node['type'] ?? null,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function runDetail(ProcedureRun $run): array
    {
        $run->loadMissing(['template', 'task', 'startedBy:id,name', 'version', 'steps']);

        $active = [];
        foreach ($run->activeNodes() as $node) {
            $nodeId = (string) ($node['id'] ?? '');
            $type = (string) ($node['type'] ?? '');
            $step = [
                'node_id' => $nodeId,
                'name' => $node['name'] ?? $type,
                'type' => $type,
                'can_advance' => ! in_array($type, ['approval', 'wait'], true),
            ];

            if ($type === 'decision') {
                $step['options'] = collect($run->outgoingEdges($nodeId))
                    ->map(fn (array $edge) => [
                        'edge_id' => $edge['id'] ?? null,
                        'label' => $edge['label'] ?? '',
                    ])
                    ->values()
                    ->all();
            }

            if ($type === 'checklist') {
                $step['checklist'] = collect($node['checklist'] ?? [])
                    ->map(fn (array $item) => [
                        'id' => $item['id'] ?? null,
                        'label' => $item['label'] ?? $item['name'] ?? '',
                        'optional' => (bool) ($item['optional'] ?? false),
                    ])
                    ->values()
                    ->all();
            }

            $active[] = $step;
        }

        return [
            ...self::runListItem($run),
            'prompt' => self::voicePrompt($run, $active),
            'active_steps' => $active,
            'source' => $run->sourceCard(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $active
     */
    public static function voicePrompt(ProcedureRun $run, array $active): string
    {
        $name = $run->template?->name ?? 'Procedura';
        $task = $run->task?->name;
        $title = $task ?: $name;

        if ($run->status?->isTerminal()) {
            return "{$title} jest {$run->status->label()}.";
        }

        if ($run->needsBegin()) {
            return "{$title} czeka na start. Powiedz „rozpocznij”, żeby ruszyć z pierwszego kroku.";
        }

        if ($active === []) {
            return "{$title} nie ma klikalnego kroku. Sprawdź run na karcie zadania.";
        }

        if (count($active) > 1) {
            $labels = collect($active)->pluck('name')->filter()->implode(', ');

            return "{$title} ma kilka równoległych kroków: {$labels}. Powiedz, który domknąć.";
        }

        $step = $active[0];
        $label = $step['name'] ?? 'krok';
        $type = $step['type'] ?? '';

        return match ($type) {
            'decision' => self::decisionPrompt($title, $label, $step['options'] ?? []),
            'checklist' => "{$title}. Krok: {$label} — checklista. Odhacz pozycje i powiedz „dalej”.",
            'approval' => "{$title}. Krok: {$label} czeka na zatwierdzenie. Tego nie da się pominąć głosem — musi kliknąć zatwierdzający.",
            'wait' => "{$title}. Krok: {$label} — oczekiwanie (timer).",
            'comment' => "{$title}. Krok: {$label}. Podaj treść komentarza i powiedz „dalej”.",
            default => "{$title}. Krok: {$label}. Powiedz „dalej”, żeby przejść.",
        };
    }

    /**
     * @param  list<array{edge_id?: mixed, label?: string}>  $options
     */
    private static function decisionPrompt(string $title, string $label, array $options): string
    {
        $choices = collect($options)
            ->map(fn (array $option, int $i) => ($i + 1).') '.($option['label'] ?: 'opcja'))
            ->implode(', ');

        return "{$title}. Krok: {$label}. Wybierz: {$choices}.";
    }
}
