<?php

namespace App\Models;

use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'name',
        'nip',
        'regon',
        'krs',
        'address',
        'city',
        'postal_code',
        'country',
        'founded_at',
        'president_name',
        'email',
        'phone',
        'notes',
    ];

    protected $casts = [
        'founded_at' => 'date',
        'country' => \App\Enums\EuropeanCountry::class,
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'company_assignments')
            ->withPivot('start_date', 'end_date', 'notes')
            ->withTimestamps();
    }
}
