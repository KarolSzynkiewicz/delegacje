<?php

namespace App\Models;

use App\Enums\ConsumptionDestination;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EquipmentStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'related_warehouse_id',
        'equipment_id',
        'equipment_variant_id',
        'type',
        'reason',
        'quantity',
        'employee_id',
        'consumed_for_type',
        'consumed_for_id',
        'notes',
        'batch_id',
        'logistics_event_id',
        'created_by',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'reason' => StockMovementReason::class,
        'quantity' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function relatedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'related_warehouse_id');
    }

    public function logisticsEvent(): BelongsTo
    {
        return $this->belongsTo(LogisticsEvent::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EquipmentVariant::class, 'equipment_variant_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function consumedFor(): MorphTo
    {
        return $this->morphTo();
    }

    public function destinationType(): ?ConsumptionDestination
    {
        return ConsumptionDestination::tryFrom((string) $this->consumed_for_type)
            ?? ($this->consumedFor ? ConsumptionDestination::tryFromModel($this->consumedFor) : null);
    }

    public function destinationLabel(): ?string
    {
        $destination = $this->consumedFor;
        $type = $this->destinationType();
        if ($destination && $type) {
            return $type->labelFor($destination);
        }

        return $this->employee?->full_name;
    }

    public function destinationMeta(): ?string
    {
        $label = $this->destinationLabel();
        if (! filled($label)) {
            return null;
        }

        $type = $this->destinationType();

        return $type ? $type->label().' · '.$label : $label;
    }

    public function destinationHref(): ?string
    {
        $destination = $this->consumedFor;
        $type = $this->destinationType();
        if (! $destination || ! $type) {
            return null;
        }

        return $type->hrefFor($destination);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => '—',
        ]);
    }

    public function title(): string
    {
        if ($this->type?->isTransfer()) {
            return 'Przemieszczenie';
        }

        return $this->reason?->label()
            ?? $this->type?->label()
            ?? 'Ruch';
    }

    public function signedQuantity(): int
    {
        return $this->type?->increasesStock()
            ? $this->quantity
            : -$this->quantity;
    }

    public function quantityLabel(): string
    {
        $signed = $this->signedQuantity();
        $prefix = $signed > 0 ? '+' : '';

        return $prefix.$signed.' szt.';
    }

    public function happenedAtLabel(): string
    {
        return self::formatHappenedAt($this->created_at);
    }

    public static function formatHappenedAt(?\Carbon\CarbonInterface $at): string
    {
        if (! $at) {
            return '—';
        }

        $time = $at->format('H:i');
        if ($at->isToday()) {
            return 'dziś, '.$time;
        }
        if ($at->isYesterday()) {
            return 'wczoraj, '.$time;
        }

        return $at->format('Y-m-d').', '.$time;
    }

    public function metaLine(): string
    {
        return collect([
            $this->variant?->sku ?? $this->equipment?->name,
            $this->warehouse?->display_name,
            $this->destinationMeta(),
            $this->creator?->name,
            $this->happenedAtLabel(),
        ])->filter()->unique()->implode(' · ');
    }
}
