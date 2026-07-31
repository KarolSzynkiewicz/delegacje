<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistent mapping of a UI "slot key" to the ProcedureTemplate that should
 * run there. Slot keys are defined ad-hoc by callers (e.g. Blade/Livewire
 * views) — this table is the only place that knows which template a given
 * slot currently points to.
 */
class ProcedureSlotBinding extends Model
{
    protected $fillable = [
        'key',
        'procedure_template_id',
        'created_by',
        'updated_by',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
