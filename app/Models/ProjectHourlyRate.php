<?php

namespace App\Models;

use App\Traits\HasDateRange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHourlyRate extends Model
{
    use HasDateRange;

    protected $fillable = [
        'project_id',
        'amount',
        'currency',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
