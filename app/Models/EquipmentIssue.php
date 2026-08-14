<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentIssue extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_GIVEN = 'given';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_DAMAGED = 'damaged';

    public const STATUS_LOST = 'lost';

    protected $fillable = [
        'equipment_id',
        'equipment_variant_id',
        'warehouse_id',
        'employee_id',
        'quantity_issued',
        'issue_date',
        'actual_return_date',
        'status',
        'notes',
        'batch_id',
        'issued_by',
        'returned_by',
    ];

    protected $casts = [
        'quantity_issued' => 'integer',
        'issue_date' => 'date',
        'actual_return_date' => 'date',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(EquipmentVariant::class, 'equipment_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by')->withDefault([
            'name' => '—',
        ]);
    }

    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getItemLabelAttribute(): string
    {
        if ($this->relationLoaded('variant') && $this->variant) {
            return $this->variant->display_name;
        }

        if ($this->relationLoaded('equipment') && $this->equipment) {
            return $this->equipment->name;
        }

        return $this->variant?->display_name ?? $this->equipment?->name ?? '—';
    }

    public function isReturnableIssue(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isPermanentIssue(): bool
    {
        return $this->status === self::STATUS_GIVEN;
    }

    public function statusLabel(): string
    {
        return self::labelForStatus($this->status);
    }

    public function statusBadgeVariant(): string
    {
        return match ($this->status) {
            self::STATUS_ISSUED => 'info',
            self::STATUS_GIVEN => 'accent',
            self::STATUS_RETURNED => 'success',
            self::STATUS_DAMAGED => 'danger',
            self::STATUS_LOST => 'warning',
            default => 'info',
        };
    }

    public function eventLabel(): string
    {
        if ($this->isPermanentIssue()) {
            return 'Wydanie bezzwrotne';
        }

        return 'Wydanie do zwrotu';
    }

    public static function labelForStatus(?string $status): string
    {
        return match ($status) {
            self::STATUS_ISSUED => 'Do zwrotu',
            self::STATUS_GIVEN => 'Bezzwrotne',
            self::STATUS_RETURNED => 'Zwrócony',
            self::STATUS_DAMAGED => 'Uszkodzony',
            self::STATUS_LOST => 'Zgubiony',
            default => $status ? ucfirst($status) : '—',
        };
    }

    /**
     * @return list<string>
     */
    public static function filterStatuses(): array
    {
        return [
            self::STATUS_ISSUED,
            self::STATUS_GIVEN,
            self::STATUS_RETURNED,
            self::STATUS_DAMAGED,
            self::STATUS_LOST,
        ];
    }

    public function markAsReturned(\Carbon\Carbon $returnDate, ?int $returnedBy = null, string $status = 'returned'): void
    {
        $this->update([
            'status' => $status,
            'actual_return_date' => $returnDate,
            'returned_by' => $returnedBy ?? auth()->id(),
        ]);
    }
}
