<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FixedCostEntry extends Model
{
    use HasFactory;

    protected $table = 'fixed_cost_entries';

    protected $fillable = [
        'name',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'accounting_date',
        'template_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'accounting_date' => 'date',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(FixedCostTemplate::class, 'template_id');
    }

    /**
     * Sprawdź czy entry już istnieje dla danego okresu i szablonu
     */
    public static function existsForPeriodAndTemplate(
        int $templateId,
        Carbon $periodStart,
        Carbon $periodEnd
    ): bool {
        return static::where('template_id', $templateId)
            ->where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->exists();
    }
}
