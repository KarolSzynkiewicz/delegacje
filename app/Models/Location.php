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
        'is_base',
    ];

    protected $casts = [
        'is_base' => 'boolean',
    ];

    /**
     * Get the projects for the location.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the base location (singleton pattern).
     * 
     * @return Location
     */
    public static function getBase(): Location
    {
        return static::base()->first() ?? static::create([
            'name' => 'Baza',
            'address' => 'Siedziba główna',
            'city' => 'Warszawa',
            'is_base' => true,
        ]);
    }

    /**
     * Scope a query to only include base locations.
     */
    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }
}
