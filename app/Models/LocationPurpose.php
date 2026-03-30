<?php

namespace App\Models;

use App\Enums\LocationPurposeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPurpose extends Model
{
    protected $fillable = ['location_id', 'purpose'];

    protected $casts = [
        'purpose' => LocationPurposeType::class,
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
