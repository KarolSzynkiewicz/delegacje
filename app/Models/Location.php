<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'postal_code',
        'contact_person',
        'phone',
        'email',
        'description',
    ];

    /**
     * Get the projects for the location.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
