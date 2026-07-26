<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureRunComment extends Model
{
    protected $fillable = [
        'procedure_run_id',
        'user_id',
        'body',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ProcedureRun::class, 'procedure_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
