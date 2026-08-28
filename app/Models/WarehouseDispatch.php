<?php

namespace App\Models;

use App\Contracts\TaskSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WarehouseDispatch extends Model implements TaskSubject
{
    use HasFactory;

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'number',
        'year',
        'sequence',
        'warehouse_id',
        'issue_date',
        'notes',
        'status',
        'issued_at',
        'issued_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'sequence' => 'integer',
        'issue_date' => 'date',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'name' => '—',
        ]);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by')->withDefault([
            'name' => '—',
        ]);
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withDefault([
            'name' => '—',
        ]);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(EquipmentIssue::class);
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(ProjectTask::class, 'subject');
    }

    public function taskCardUrl(): string
    {
        return route('warehouse-dispatches.show', $this);
    }

    public function taskCardLabel(): string
    {
        return $this->number ? 'Dokument '.$this->number : 'Dokument ZW';
    }

    public function taskCardIcon(): string
    {
        return 'bi-box-seam';
    }

    public function taskName(): string
    {
        return 'Kompletacja '.$this->number;
    }

    public function taskDescription(): string
    {
        $summary = $this->summary();
        $lines = [
            'Zlecenie wydania '.$summary['number'],
            'Magazyn: '.$summary['warehouse'],
            'Data: '.$summary['issue_date'],
            'Osoby / pozycje: '.$summary['people_count'].' os. · '.$summary['position_count'].' poz.',
        ];

        if (filled($summary['notes'])) {
            $lines[] = 'Notatka: '.$summary['notes'];
        }

        foreach ($summary['recipients'] as $recipient) {
            foreach ($recipient['lines'] as $line) {
                $lines[] = $recipient['name'].' — '.$line['item'].' '.$line['variant'].' × '.$line['quantity'];
            }
        }

        $lines[] = 'Dokument ZW: '.$this->taskCardUrl();

        return implode("\n", $lines);
    }

    /**
     * @return array{year: int, sequence: int, number: string}
     */
    public static function nextNumber(int $year): array
    {
        $last = static::query()
            ->where('year', $year)
            ->orderByDesc('sequence')
            ->lockForUpdate()
            ->first();

        $sequence = (int) ($last?->sequence ?? 0) + 1;

        return [
            'year' => $year,
            'sequence' => $sequence,
            'number' => sprintf('ZW-%d-%04d', $year, $sequence),
        ];
    }

    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isClosed(): bool
    {
        return $this->isIssued() || $this->isPartial() || $this->isCancelled();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RESERVED => 'Do kompletacji',
            self::STATUS_PARTIAL => 'Częściowo wydane',
            self::STATUS_CANCELLED => 'Anulowane',
            default => 'Wydane',
        };
    }

    public function recipientCount(): int
    {
        return $this->issues->pluck('employee_id')->unique()->count();
    }

    public function positionCount(): int
    {
        return $this->issues->count();
    }

    /**
     * @return array{number: string, warehouse: string, issue_date: string, notes: ?string, issuer_name: string, people_count: int, position_count: int, recipients: list<array{name: string, lines: list<array{item: string, variant: string, quantity: int, kind: string}>}>}
     */
    public function summary(): array
    {
        $this->loadMissing(['warehouse.location', 'creator', 'issuer', 'issues.employee', 'issues.equipment', 'issues.variant']);

        $recipients = [];
        foreach ($this->issues->sortBy(fn (EquipmentIssue $issue) => $issue->employee?->last_name.' '.$issue->employee?->first_name) as $issue) {
            if ($issue->status === EquipmentIssue::STATUS_CANCELLED) {
                continue;
            }
            $employeeId = (int) $issue->employee_id;
            if (! isset($recipients[$employeeId])) {
                $recipients[$employeeId] = [
                    'name' => $issue->employee?->full_name ?? '—',
                    'lines' => [],
                ];
            }

            $recipients[$employeeId]['lines'][] = [
                'id' => $issue->id,
                'item' => $issue->equipment?->name ?? '—',
                'variant' => $issue->variant?->kind_label ?? '—',
                'quantity' => (int) $issue->quantity_issued,
                'kind' => $issue->equipment?->returnable ? 'Do zwrotu' : 'Bezzwrotne',
                'status' => $issue->statusLabel(),
                'status_variant' => $issue->statusBadgeVariant(),
                'is_reserved' => $issue->isReserved(),
            ];
        }

        return [
            'number' => $this->number,
            'warehouse' => $this->warehouse?->display_name ?? '—',
            'issue_date' => $this->issue_date?->format('d.m.Y') ?? '—',
            'notes' => $this->notes,
            'issuer_name' => $this->creator?->name ?? '—',
            'fulfilled_by' => $this->issuer?->name,
            'status' => $this->statusLabel(),
            'is_reserved' => $this->isReserved(),
            'people_count' => $this->recipientCount(),
            'position_count' => $this->positionCount(),
            'recipients' => array_values($recipients),
        ];
    }
}
