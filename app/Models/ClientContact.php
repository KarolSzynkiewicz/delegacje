<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'role',
        'email',
        'phone',
        'is_primary',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contactAttempts(): HasMany
    {
        return $this->hasMany(ClientContactAttempt::class)->latest('contacted_at');
    }

    public function label(): string
    {
        $role = $this->role ? ' · '.$this->role : '';

        return $this->name.$role;
    }
}
