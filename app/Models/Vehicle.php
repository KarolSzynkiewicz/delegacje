<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_number',
        'brand',
        'model',
        'capacity',
        'technical_condition',
        'inspection_valid_to',
        'notes'
    ];

    protected $casts = [
        'inspection_valid_to' => 'date',
    ];
}
