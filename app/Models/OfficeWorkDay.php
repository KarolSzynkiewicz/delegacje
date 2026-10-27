<?php

namespace App\Models;

use App\Enums\OfficeWorkLocation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeWorkDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_profile_id',
        'work_date',
        'starts_at',
        'ends_at',
        'hours',
        'location',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'hours' => 'decimal:2',
        'location' => OfficeWorkLocation::class,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'office_profile_id');
    }
}
