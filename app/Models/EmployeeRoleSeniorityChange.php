<?php

namespace App\Models;

use App\Enums\RoleSeniority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRoleSeniorityChange extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'role_id',
        'from_seniority',
        'to_seniority',
        'changed_by',
        'comment',
        'created_at',
    ];

    protected $casts = [
        'from_seniority' => 'integer',
        'to_seniority' => 'integer',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fromLevel(): ?RoleSeniority
    {
        return RoleSeniority::fromPivot($this->from_seniority);
    }

    public function toLevel(): ?RoleSeniority
    {
        return RoleSeniority::fromPivot($this->to_seniority);
    }
}
