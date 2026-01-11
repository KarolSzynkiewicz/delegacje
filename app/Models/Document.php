<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_periodic',
        'is_required',
    ];

    protected $casts = [
        'is_periodic' => 'boolean',
        'is_required' => 'boolean',
    ];

    /**
     * Get all employee documents of this type.
     */
    public function employeeDocuments(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }
}
