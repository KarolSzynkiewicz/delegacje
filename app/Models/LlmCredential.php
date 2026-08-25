<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmCredential extends Model
{
    protected $fillable = [
        'provider',
        'api_key',
        'model',
        'is_active',
        'last_used_at',
        'created_by',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Klucz nie może wyciec przez toArray(), JSON API ani logi wyjątków.
     *
     * @var array<int, string>
     */
    protected $hidden = ['api_key'];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
