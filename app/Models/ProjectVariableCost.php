<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVariableCost extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'amount',
        'currency',
        'incurred_date',
        'category',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'incurred_date' => 'date',
    ];

    /**
     * Predefiniowane kategorie kosztów zmiennych (sugerowane w UI,
     * pole pozostaje dowolnym stringiem dla elastyczności).
     *
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            'material'      => 'Materiały',
            'equipment'     => 'Sprzęt jednorazowy',
            'subcontractor' => 'Podwykonawcy',
            'food'          => 'Wyżywienie / catering',
            'tools'         => 'Narzędzia',
            'permits'       => 'Pozwolenia / opłaty urzędowe',
            'other'         => 'Inne',
        ];
    }

    /**
     * Get the project that owns this variable cost.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
