<?php

namespace App\Models;

use App\Enums\ProcedureRunStatus;
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
        'status'              => ProcedureRunStatus::class,
        'definition_snapshot' => 'array',
        'path'                => 'array',
        'variables'           => 'array',
        'started_at'          => 'datetime',
        'finished_at'         => 'datetime',
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

    /** Outgoing edges from a given node id. */
    public function outgoingEdges(string $nodeId): array
    {
        return array_values(array_filter(
            $this->definition_snapshot['edges'] ?? [],
            fn ($e) => ($e['from'] ?? null) === $nodeId
        ));
    }

    /** 0.0–1.0 progress fraction. */
    public function progress(): float
    {
        $nodes = array_filter(
            $this->definition_snapshot['nodes'] ?? [],
            fn ($n) => ($n['type'] ?? '') !== 'note'
        );
        $total = count($nodes);
        if ($total === 0) {
            return 0.0;
        }
        $completed = $this->steps()->whereNotNull('completed_at')->count();
        return round(min($completed / $total, 1.0), 2);
    }
}
