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
        'procedure_template_version_id',
        'active_node_ids',
        'join_tokens',
        'subject_type',
        'subject_id',
        'slot_key',
        'path',
        'status',
        'variables',
        'started_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'status' => ProcedureRunStatus::class,
        'active_node_ids' => 'array',
        'join_tokens' => 'array',
        'path' => 'array',
        'variables' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplateVersion::class, 'procedure_template_version_id');
    }

    /** @return array{nodes: array, edges: array} */
    public function definition(): array
    {
        return $this->version?->definition ?? ['nodes' => [], 'edges' => []];
    }

    /** @return list<string> */
    public function activeNodeIds(): array
    {
        return array_values($this->active_node_ids ?? []);
    }

    /** @return list<array<string, mixed>> */
    public function activeNodes(): array
    {
        return collect($this->activeNodeIds())
            ->map(fn (string $id) => $this->findNodeById($id))
            ->filter()
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function completedNodeIds(): array
    {
        if ($this->relationLoaded('steps')) {
            return $this->steps->whereNotNull('completed_at')->pluck('node_id')->unique()->values()->all();
        }

        return $this->steps()->whereNotNull('completed_at')->pluck('node_id')->unique()->values()->all();
    }

    /**
     * Highlight state for the run-flow canvas.
     *
     * @return array{completed: list<string>, active: list<string>, waiting: list<string>}
     */
    public function flowHighlight(): array
    {
        $completed = $this->completedNodeIds();
        $active = $this->activeNodeIds();
        $waiting = [];

        foreach (array_keys($this->join_tokens ?? []) as $nodeId) {
            $nodeId = (string) $nodeId;
            if (! in_array($nodeId, $active, true) && ! in_array($nodeId, $completed, true)) {
                $waiting[] = $nodeId;
            }
        }

        return [
            'completed' => $completed,
            'active' => $active,
            'waiting' => $waiting,
        ];
    }

    /** @deprecated Use activeNodes() — kept for transitional callers. */
    public function currentNode(): ?array
    {
        $ids = $this->activeNodeIds();

        return $ids === [] ? null : $this->findNodeById($ids[0]);
    }

    /** @return array<string, mixed>|null */
    public function findNodeById(string $nodeId): ?array
    {
        foreach ($this->definition()['nodes'] ?? [] as $node) {
            if (($node['id'] ?? null) === $nodeId) {
                return $node;
            }
        }

        return null;
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
            $this->definition()['edges'] ?? [],
            fn ($e) => ($e['from'] ?? null) === $nodeId
        ));
    }

    /** Incoming edges to a given node id. */
    public function incomingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->definition()['edges'] ?? [],
            fn ($e) => ($e['to'] ?? null) === $nodeId
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

        $remaining = $this->estimateStepsToEndFromActive();
        $openActive = count($this->activeNodeIds());
        $total = max($completed + $openActive + $remaining, $completed, 1);
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

    /** Minimalna liczba węzłów od aktywnych do najbliższego końca (BFS, merge-aware). */
    protected function estimateStepsToEndFromActive(): int
    {
        $activeIds = $this->activeNodeIds();

        if ($activeIds === []) {
            return 1;
        }

        $nodes = collect($this->definition()['nodes'] ?? [])
            ->keyBy(fn (array $node) => (string) ($node['id'] ?? ''));

        $edges = $this->definition()['edges'] ?? [];
        $queue = [];
        $visited = [];

        foreach ($activeIds as $nodeId) {
            $queue[] = [$nodeId, 0];
        }

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
