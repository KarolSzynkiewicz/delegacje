<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pozycja rejestru kosztów — otoczka nad jednym rekordem źródłowym
 * (koszt ogólnofirmowy, projektowy, transportu albo serwis pojazdu).
 *
 * Nie przechowuje kwoty, waluty ani daty: te zawsze czyta ze źródła,
 * więc poprawka na karcie transportu czy naprawy od razu widać w rejestrze.
 * Otoczka trzyma spółkę, miesiąc rozliczeniowy, nazwę rejestrową,
 * komentarz i znacznik „nieistotny finansowo”.
 */
class CostEntry extends Model
{
    public const SOURCE_FIXED = 'fixed_cost_entry';

    public const SOURCE_PROJECT = 'project_variable_cost';

    public const SOURCE_TRANSPORT = 'transport_cost';

    public const SOURCE_REPAIR = 'vehicle_repair';

    protected $fillable = [
        'source_type',
        'source_id',
        'company_id',
        'name',
        'period_year',
        'period_month',
        'financially_irrelevant',
        'comment',
    ];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'financially_irrelevant' => 'boolean',
    ];

    /**
     * @return array<string, string> alias morph → etykieta rodzaju
     */
    public static function sourceTypeOptions(): array
    {
        return [
            self::SOURCE_FIXED => 'Ogólnofirmowy',
            self::SOURCE_PROJECT => 'Projektowy',
            self::SOURCE_TRANSPORT => 'Transport',
            self::SOURCE_REPAIR => 'Serwis',
        ];
    }

    /**
     * @return array<string, class-string<Model>>
     */
    public static function sourceModels(): array
    {
        return [
            self::SOURCE_FIXED => FixedCostEntry::class,
            self::SOURCE_PROJECT => ProjectVariableCost::class,
            self::SOURCE_TRANSPORT => TransportCost::class,
            self::SOURCE_REPAIR => VehicleRepair::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function monthOptions(): array
    {
        return [
            1 => 'Styczeń',
            2 => 'Luty',
            3 => 'Marzec',
            4 => 'Kwiecień',
            5 => 'Maj',
            6 => 'Czerwiec',
            7 => 'Lipiec',
            8 => 'Sierpień',
            9 => 'Wrzesień',
            10 => 'Październik',
            11 => 'Listopad',
            12 => 'Grudzień',
        ];
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return self::sourceTypeOptions()[$this->source_type] ?? $this->source_type;
    }

    public function getPeriodLabelAttribute(): string
    {
        $month = self::monthOptions()[$this->period_month] ?? $this->period_month;

        return "{$month} {$this->period_year}";
    }

    public function sourceBadgeVariant(): string
    {
        return match ($this->source_type) {
            self::SOURCE_FIXED => 'secondary',
            self::SOURCE_PROJECT => 'info',
            self::SOURCE_TRANSPORT => 'accent',
            self::SOURCE_REPAIR => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Kwota ze źródła (serwis trzyma ją w `price`, reszta w `amount`).
     */
    public function amount(): ?float
    {
        $source = $this->source;
        if (! $source) {
            return null;
        }

        $value = $source instanceof VehicleRepair ? $source->price : $source->amount;

        return $value === null ? null : (float) $value;
    }

    public function currency(): ?string
    {
        $currency = $this->source?->currency;

        return $currency ? strtoupper((string) $currency) : null;
    }

    /**
     * Data poniesienia/księgowania ze źródła — do wyświetlenia obok miesiąca rozliczeniowego.
     */
    public function sourceDate(): ?CarbonInterface
    {
        $source = $this->source;

        return match (true) {
            $source instanceof FixedCostEntry => $source->accounting_date ?? $source->period_start,
            $source instanceof ProjectVariableCost => $source->incurred_date ?? $source->created_at,
            $source instanceof TransportCost => $source->cost_date,
            $source instanceof VehicleRepair => $source->end_date ?? $source->start_date,
            default => null,
        };
    }

    /**
     * Kontekst źródła: projekt, pojazd, kategoria — jedna linijka pod nazwą.
     */
    public function sourceContext(): ?string
    {
        $source = $this->source;

        return match (true) {
            $source instanceof FixedCostEntry => $source->category
                ? (FixedCostTemplate::categoryOptions()[$source->category] ?? $source->category)
                : $source->template?->name,
            $source instanceof ProjectVariableCost => $source->project?->name,
            $source instanceof TransportCost => TransportCost::costTypeLabels()[$source->cost_type] ?? $source->cost_type,
            $source instanceof VehicleRepair => trim(($source->vehicle?->registration_number ?? '').' · '.$source->action_type?->label(), ' ·'),
            default => null,
        };
    }

    public function sourceUrl(): ?string
    {
        if (! $this->source_id) {
            return null;
        }

        return match ($this->source_type) {
            self::SOURCE_FIXED => route('fixed-cost-entries.show', $this->source_id),
            self::SOURCE_PROJECT => route('project-variable-costs.show', $this->source_id),
            self::SOURCE_TRANSPORT => route('transport-costs.show', $this->source_id),
            self::SOURCE_REPAIR => route('vehicle-repairs.show', $this->source_id),
            default => null,
        };
    }
}
