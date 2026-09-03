<?php

namespace App\Models;

use App\Services\ProcedureStepHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureRunStep extends Model
{
    protected $fillable = [
        'procedure_run_id',
        'spawned_from_step_id',
        'node_id',
        'node_name',
        'node_type',
        'entered_at',
        'completed_at',
        'resume_at',
        'approval_request_id',
        'performed_by',
        'data',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'completed_at' => 'datetime',
        'resume_at' => 'datetime',
        'data' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ProcedureRun::class, 'procedure_run_id');
    }

    public function spawnedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'spawned_from_step_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Co faktycznie wyniknęło z tego kroku (wybór, komentarz, skutek akcji…).
     *
     * @return array{text: string, url: string|null, tone: string|null}|null
     */
    public function historyOutcome(): ?array
    {
        return ProcedureStepHistory::outcome($this);
    }

    /**
     * Ramka wizualna kroku w historii przebiegu.
     *
     * @param  array<string, mixed>|null  $node
     * @return array{
     *     icon: string,
     *     bi: string,
     *     color: string,
     *     name: string,
     *     type_label: string,
     *     show_type: bool,
     *     description: string|null,
     *     assignee_id: int
     * }
     */
    public function historyFrame(?array $node): array
    {
        $meta = ProcedureRun::nodeTypeMeta($this->node_type);
        $name = trim((string) ($node['name'] ?? $this->node_name ?: $meta['label']));
        if ($name === '') {
            $name = $meta['label'];
        }

        $normalized = mb_strtolower($name);
        $showType = $normalized !== mb_strtolower($meta['label'])
            && $normalized !== mb_strtolower($meta['default_name']);

        $description = trim((string) ($node['description'] ?? ''));
        if ($description !== '' && mb_strtolower($description) === $normalized) {
            $description = '';
        }

        return [
            'icon' => (string) ($node['icon'] ?? $meta['icon']),
            'bi' => $meta['bi'],
            'color' => (string) ($node['color'] ?? $meta['color']),
            'name' => $name,
            'type_label' => $meta['label'],
            'show_type' => $showType,
            'description' => $description !== '' ? $description : null,
            'assignee_id' => (int) ($node['assigned_user_id'] ?? 0),
        ];
    }
}
