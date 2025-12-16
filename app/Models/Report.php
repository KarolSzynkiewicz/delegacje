<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'filters',
        'generated_by',
        'generated_at',
        'file_path',
        'format',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'json',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user who generated the report.
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the delegations included in the report.
     */
    public function delegations()
    {
        // TODO: Implement relationship to delegations
        return $this->belongsToMany(Delegation::class);
    }
}
