<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_template_id',
        'definition',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'definition' => 'array',
        'changed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
