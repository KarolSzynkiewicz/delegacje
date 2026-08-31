<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthKeyPair extends Model
{
    protected $table = 'oauth_key_pairs';

    protected $fillable = [
        'private_key',
        'public_key',
    ];

    protected $hidden = [
        'private_key',
        'public_key',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'public_key' => 'encrypted',
    ];
}
