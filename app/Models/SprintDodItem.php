<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintDodItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sprint_id',
        'name',
        'completed_at',
        'position',
        'created_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function toggleCompleted(): void
    {
        $this->update([
            'completed_at' => $this->completed_at ? null : now(),
        ]);
    }
}
