<?php

namespace App\Models;

use App\Enums\ProcedureRunStatus;
use App\Enums\ProcedureSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProcedureRun extends Model
{
    protected $fillable = [
        'procedure_template_id',
        'definition_snapshot',
        'subject_type',
        'subject_id',
        'slot_key',
        'current_node_id',
        'path',
        'status',
        'variables',
        'started_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ProcedureRunStatus::class,
        'definition_snapshot' => 'array',
        'path' => 'array',
        'variables' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }

    /** The arbitrary model this run was started for (e.g. a RecruitmentProcess). */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcedureRunStep::class)->orderBy('entered_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ProcedureRunComment::class)->orderBy('created_at');
    }

    public function task(): HasOne
    {
        return $this->hasOne(ProjectTask::class, 'procedure_run_id');
    }

    /** Returns the current node array from the snapshot, or null if not found. */
    public function currentNode(): ?array
    {
        $nodes = $this->definition_snapshot['nodes'] ?? [];
        foreach ($nodes as $node) {
            if (($node['id'] ?? null) === $this->current_node_id) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Link do rekordu, którego dotyczy przebieg (samochód, pracownik, …).
     *
     * @return array{url: string, label: string, icon: string}|null
     */
    public function sourceCard(): ?array
    {
        $type = ProcedureSubjectType::tryFrom((string) $this->subject_type);
        if ($type === null || ! $this->subject_id) {
            return null;
        }

        $this->loadMissing('subject');
        $subject = $this->subject;
        if (! $subject) {
            return null;
        }

        return $type->sourceCardFor($subject);
    }

    /** Outgoing edges from a given node id. */
    public function outgoingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->definition_snapshot['edges'] ?? [],
            fn ($e) => ($e['from'] ?? null) === $nodeId
        ));
    }

    public static function nodeTypeLabel(?string $type): string
    {
        return match ($type) {
            'start' => 'Start',
            'end' => 'Koniec',
            'task' => 'Krok',
            'checklist' => 'Checklista',
            'decision' => 'Decyzja',
            'wait' => 'Oczekiwanie',
            'note' => 'Notatka',
            default => $type ?: '—',
        };
    }

    /** 0.0–1.0 progress fraction. */
    public function progress(): float
    {
        return $this->progressMetrics()['fraction'];
    }

    /**
     * Metryki postępu uwzględniające gałęzie — liczy wzdłuż bieżącej ścieżki,
     * nie wszystkich węzłów w definicji procedury.
     *
     * @return array{fraction: float, percent: int, completed: int, total: int, label: string}
     */
    public function progressMetrics(): array
    {
        $completed = $this->completedStepCount();

        if ($this->status === ProcedureRunStatus::FINISHED) {
            return [
                'fraction' => 1.0,
                'percent' => 100,
                'completed' => $completed,
                'total' => max($completed, 1),
                'label' => $completed > 0 ? "krok {$completed} · ukończono" : 'ukończono',
            ];
        }

        if ($this->status === ProcedureRunStatus::ABANDONED) {
            $total = max($completed, 1);
            $fraction = min($completed / $total, 1.0);

            return [
                'fraction' => round($fraction, 2),
                'percent' => (int) round($fraction * 100),
                'completed' => $completed,
                'total' => $total,
                'label' => "krok {$completed} · porzucono",
            ];
        }

        $remaining = $this->estimateStepsToEndFromCurrent();
        $hasOpenStep = $this->hasOpenStepOnCurrentNode();
        $total = max($completed + ($hasOpenStep ? 1 : 0) + $remaining, $completed, 1);
        $fraction = min($completed / $total, 0.99);

        return [
            'fraction' => round($fraction, 2),
            'percent' => (int) round($fraction * 100),
            'completed' => $completed,
            'total' => $total,
            'label' => "krok {$completed} z ~{$total}",
        ];
    }

    protected function completedStepCount(): int
    {
        if ($this->relationLoaded('steps')) {
            return $this->steps->whereNotNull('completed_at')->count();
        }

        return $this->steps()->whereNotNull('completed_at')->count();
    }

    protected function hasOpenStepOnCurrentNode(): bool
    {
        if (! $this->current_node_id) {
            return false;
        }

        if ($this->relationLoaded('steps')) {
            return $this->steps
                ->where('node_id', $this->current_node_id)
                ->whereNull('completed_at')
                ->isNotEmpty();
        }

        return $this->steps()
            ->where('node_id', $this->current_node_id)
            ->whereNull('completed_at')
            ->exists();
    }

    /** Minimalna liczba węzłów od bieżącego (bez niego) do najbliższego końca. */
    protected function estimateStepsToEndFromCurrent(): int
    {
        $currentId = $this->current_node_id;

        if (! $currentId) {
            return 1;
        }

        $nodes = collect($this->definition_snapshot['nodes'] ?? [])
            ->keyBy(fn (array $node) => (string) ($node['id'] ?? ''));

        $current = $nodes->get($currentId);

        if ($current && ($current['type'] ?? '') === 'end') {
            return 0;
        }

        $edges = $this->definition_snapshot['edges'] ?? [];
        $queue = [[$currentId, 0]];
        $visited = [];

        while ($queue !== []) {
            [$nodeId, $depth] = array_shift($queue);

            if (isset($visited[$nodeId])) {
                continue;
            }

            $visited[$nodeId] = true;

            $node = $nodes->get($nodeId);

            if ($node && ($node['type'] ?? '') === 'end') {
                return $depth;
            }

            foreach ($edges as $edge) {
                if (($edge['from'] ?? null) !== $nodeId) {
                    continue;
                }

                $to = (string) ($edge['to'] ?? '');

                if ($to !== '' && ! isset($visited[$to])) {
                    $queue[] = [$to, $depth + 1];
                }
            }
        }

        return 1;
    }
}
