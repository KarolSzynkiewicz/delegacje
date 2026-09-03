<?php

namespace App\Models;

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
        'performed_by',
        'data',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
        'completed_at' => 'datetime',
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
}
