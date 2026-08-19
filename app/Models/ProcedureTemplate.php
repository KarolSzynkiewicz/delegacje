<?php

namespace App\Models;

use App\Enums\ProcedureSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcedureTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'subject_type',
        'description',
        'tags',
        'definition',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'definition' => 'array',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ProcedureRun::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProcedureTemplateVersion::class)->orderByDesc('changed_at');
    }

    public function nodeCount(): int
    {
        $nodes = $this->definition['nodes'] ?? [];

        return count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') !== 'note'));
    }

    public function subjectType(): ?ProcedureSubjectType
    {
        return ProcedureSubjectType::tryFrom((string) $this->subject_type);
    }
}
