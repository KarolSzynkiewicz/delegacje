<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeLeaveAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_profile_id',
        'year',
        'days',
        'reason',
        'effective_on',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'days' => 'integer',
        'effective_on' => 'date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'office_profile_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCredit(): bool
    {
        return $this->days > 0;
    }

    public function signedLabel(): string
    {
        $days = (int) $this->days;
        $prefix = $days > 0 ? '+' : '';

        return $prefix.$days;
    }
}
