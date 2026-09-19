<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'event_type' => NotificationEvent::class,
        'channel' => NotificationChannel::class,
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
