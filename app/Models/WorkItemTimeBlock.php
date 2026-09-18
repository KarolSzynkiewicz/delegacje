<?php

namespace App\Models;

use App\Enums\WorkItemTimeBlockKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class WorkItemTimeBlock extends Model
{
    protected $fillable = [
        'work_item_id',
        'kind',
        'title',
        'user_id',
        'starts_at',
        'ends_at',
        'all_day',
        'created_by_id',
    ];

    protected $casts = [
        'kind' => WorkItemTimeBlockKind::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    protected $attributes = [
        'kind' => 'item',
    ];

    public function workItem(): BelongsTo
    {
        return $this->belongsTo(WorkItem::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(WorkItem::class, 'work_item_time_block_items')
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isSession(): bool
    {
        return $this->kind === WorkItemTimeBlockKind::Session;
    }

    /**
     * @return Collection<int, WorkItem>
     */
    public function openItems(): Collection
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        return $items
            ->filter(fn (WorkItem $item) => $item->status->isOpen())
            ->values();
    }

    public function displayTitle(): string
    {
        $name = trim((string) $this->title);
        if ($name !== '') {
            return $name;
        }
        if (! $this->isSession()) {
            return (string) ($this->workItem?->title ?? 'Blok');
        }

        return $this->itemCountLabel($this->openItems()->count());
    }

    public function itemCountLabel(?int $count = null): string
    {
        $count ??= $this->openItems()->count();
        if ($count === 0) {
            return 'Sesja';
        }

        $mod100 = $count % 100;
        if ($mod100 >= 12 && $mod100 <= 14) {
            return $count.' zadań';
        }

        return match ($count % 10) {
            1 => $count.' zadanie',
            2, 3, 4 => $count.' zadania',
            default => $count.' zadań',
        };
    }

    public function label(): string
    {
        if ($this->all_day) {
            return $this->starts_at->format('d.m').' · cały dzień';
        }

        return $this->starts_at->format('d.m H:i').'–'.$this->ends_at->format('H:i');
    }
}
