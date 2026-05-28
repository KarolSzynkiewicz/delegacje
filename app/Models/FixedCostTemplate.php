<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FixedCostTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'currency',
        'category',
        'interval_type',
        'interval_day',
        'start_date',
        'end_date',
        'notes',
        'is_active',
    ];

    /**
     * Predefiniowane kategorie kosztów stałych firmowych
     * (cost-centers / overhead grouping).
     *
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            'office'      => 'Biuro (czynsze biurowe, media)',
            'accounting'  => 'Księgowość / prawnicy',
            'software'    => 'Oprogramowanie / licencje',
            'leasing'     => 'Leasing aut firmowych',
            'insurance'   => 'Ubezpieczenia',
            'marketing'   => 'Marketing',
            'hr'          => 'HR / rekrutacje',
            'bank'        => 'Bank / prowizje',
            'taxes'       => 'Podatki / opłaty publiczne',
            'other'       => 'Inne',
        ];
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'interval_day' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FixedCostEntry::class, 'template_id');
    }

    /**
     * Sprawdź czy szablon jest aktywny dla danego okresu
     */
    public function isActiveForPeriod(Carbon $periodStart, Carbon $periodEnd): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && $periodEnd->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $periodStart->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
