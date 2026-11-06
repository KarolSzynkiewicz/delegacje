<?php

namespace App\Models;

use App\Enums\ClientContactChannel;
use App\Enums\ClientContactOutcome;
use App\Enums\ClientContactPurpose;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContactAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'project_id',
        'client_contact_id',
        'user_id',
        'channel',
        'purpose',
        'outcome',
        'contacted_at',
        'next_action_at',
        'summary',
    ];

    protected $casts = [
        'channel' => ClientContactChannel::class,
        'purpose' => ClientContactPurpose::class,
        'outcome' => ClientContactOutcome::class,
        'contacted_at' => 'datetime',
        'next_action_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ClientContact::class, 'client_contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
