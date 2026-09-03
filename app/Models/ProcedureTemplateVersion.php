<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcedureTemplateVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'procedure_template_id',
        'version_number',
        'definition',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'definition' => 'array',
        'changed_at' => 'datetime',
        'version_number' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProcedureTemplate::class, 'procedure_template_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ProcedureRun::class, 'procedure_template_version_id');
    }

    public function label(): string
    {
        return 'v'.$this->version_number;
    }

    public function nodeCount(): int
    {
        $nodes = $this->definition['nodes'] ?? [];

        return count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') !== 'note'));
    }
}
