<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'role_id',
        'a1_valid_from',
        'a1_valid_to',
        'document_1',
        'document_2',
        'document_3',
        'notes'
    ];

    protected $casts = [
        'a1_valid_from' => 'date',
        'a1_valid_to' => 'date',
    ];

    /**
     * Get the role associated with the employee.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get all delegations for this employee.
     */
    public function delegations()
    {
        return $this->hasMany(Delegation::class);
    }

    /**
     * Get the full name of the employee.
     */
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
