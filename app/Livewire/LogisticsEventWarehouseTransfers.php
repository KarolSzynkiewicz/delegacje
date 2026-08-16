<?php

namespace App\Livewire;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\StockMovementType;
use App\Models\Equipment;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Models\LogisticsEvent;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LogisticsEventWarehouseTransfers extends Component
{
    public int $logisticsEventId;

    public bool $adding = false;

    public int $warehouseId;

    public $targetWarehouseId = null;

    public ?string $notes = null;

    public $addEquipmentId = null;

    public string $equipmentSearch = '';

    public $addVariantId = null;

    public int $addQuantity = 1;

    /** @var array<int, array{variant_id: int, quantity: int}> */
    public array $lines = [];

    private ?Collection $catalogCache = null;

    public function mount(LogisticsEvent $event): void
    {
        $this->logisticsEventId = $event->id;
        $this->warehouseId = app(WarehouseService::class)->current()->id;
        $this->targetWarehouseId = $this->otherWarehouseId($this->warehouseId);
    }

    public function startAdding(): void
    {
        if (! $this->canAdd()) {
            return;
        }

        $this->adding = true;
        $this->targetWarehouseId = $this->otherWarehouseId((int) $this->warehouseId);
        $this->resetValidation();
    }

    public function cancelAdding(): void
    {
        $this->adding = false;
        $this->lines = [];
        $this->notes = null;
        $this->clearEquipment();
        $this->addQuantity = 1;
        $this->resetValidation();
    }

    public function updatedWarehouseId(): void
    {
        $this->catalogCache = null;
        $this->lines = [];
        $this->clearEquipment();

        if ((int) $this->targetWarehouseId === (int) $this->warehouseId) {
            $this->targetWarehouseId = $this->otherWarehouseId((int) $this->warehouseId);
        }
    }

    public function updatedAddEquipmentId(): void
    {
        $this->addVariantId = null;
        $variants = $this->variantsForSelectedType();
        if ($variants->count() === 1) {
            $this->addVariantId = $variants->first()->id;
        }
    }

    public function updatedEquipmentSearch(): void
    {
        $selectedName = $this->addEquipmentId
            ? $this->catalog()->firstWhere('id', (int) $this->addEquipmentId)?->name
            : null;

        if ($selectedName !== null && $this->equipmentSearch === $selectedName) {
            return;
        }

        $this->addEquipmentId = null;
        $this->addVariantId = null;
    }

    public function selectEquipment(int $id): void
    {
        $type = $this->catalog()->firstWhere('id', $id);
        if (! $type) {
            return;
        }

        $this->addEquipmentId = $type->id;
        $this->equipmentSearch = $type->name;
        $this->updatedAddEquipmentId();
    }

    public function clearEquipment(): void
    {
        $this->addEquipmentId = null;
        $this->equipmentSearch = '';
        $this->addVariantId = null;
    }

    public function addLine(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'addEquipmentId' => 'required|exists:equipment,id',
            'addVariantId' => 'required|exists:equipment_variants,id',
            'addQuantity' => 'required|integer|min:1',
        ], [], [
            'addEquipmentId' => 'pozycja',
            'addVariantId' => 'wariant',
            'addQuantity' => 'ilość',
        ]);

        $variantId = (int) $this->addVariantId;
        $quantity = (int) $this->addQuantity;
        $remaining = $this->remainingFor($variantId);

        if ($quantity > $remaining) {
            $this->addError('addQuantity', "Niewystarczająca ilość w magazynie. Dostępne: {$remaining}.");

            return;
        }

        $existingIndex = collect($this->lines)->search(
            fn ($line) => (int) $line['variant_id'] === $variantId
        );

        if ($existingIndex !== false) {
            $this->lines[$existingIndex]['quantity'] = (int) $this->lines[$existingIndex]['quantity'] + $quantity;
        } else {
            $this->lines[] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        $this->addQuantity = 1;
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(EquipmentService $equipmentService): void
    {
        if (! $this->canAdd()) {
            $this->addError('action', 'Nie można dodać przemieszczenia do tego zdarzenia.');

            return;
        }

        $this->validate([
            'warehouseId' => 'required|integer|exists:warehouses,id',
            'targetWarehouseId' => 'required|integer|exists:warehouses,id|different:warehouseId',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:equipment_variants,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'lines.required' => 'Dodaj co najmniej jedną pozycję do przemieszczenia.',
            'lines.min' => 'Dodaj co najmniej jedną pozycję do przemieszczenia.',
            'targetWarehouseId.different' => 'Wybierz inny magazyn docelowy niż źródłowy.',
        ]);

        try {
            $equipmentService->transferStockLines(
                $this->fromWarehouse(),
                Warehouse::query()->findOrFail((int) $this->targetWarehouseId),
                $this->lines,
                $this->event(),
                filled($this->notes) ? $this->notes : null
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
            if ($e->validator->errors()->has('quantity')) {
                $this->addError('lines', $e->validator->errors()->first('quantity'));
            }
            if ($e->validator->errors()->has('targetWarehouseId')) {
                $this->addError('targetWarehouseId', $e->validator->errors()->first('targetWarehouseId'));
            }
            if ($e->validator->errors()->has('logisticsEventId')) {
                $this->addError('action', $e->validator->errors()->first('logisticsEventId'));
            }

            return;
        }

        $this->cancelAdding();
        session()->flash('warehouseTransferSuccess', 'Przemieszczono sprzęt między magazynami.');
    }

    public function remainingFor(int $variantId): int
    {
        $variant = $this->catalog()->pluck('variants')->flatten()->firstWhere('id', $variantId);
        $available = $variant?->availableIn($this->fromWarehouse()) ?? 0;
        $inCart = (int) collect($this->lines)
            ->filter(fn ($line) => (int) $line['variant_id'] === $variantId)
            ->sum(fn ($line) => (int) $line['quantity']);

        return max(0, $available - $inCart);
    }

    public function render()
    {
        $catalog = $this->catalog();
        $selectedType = $catalog->firstWhere('id', (int) $this->addEquipmentId);
        $warehouses = app(WarehouseService::class)->all();

        return view('livewire.logistics-event-warehouse-transfers', [
            'event' => $this->event(),
            'warehouses' => $warehouses,
            'fromWarehouse' => $this->fromWarehouse(),
            'groups' => $this->transferGroups(),
            'canAdd' => $this->canAdd(),
            'emptyHint' => $this->emptyHint(),
            'equipmentMatches' => $this->equipmentMatches(),
            'catalog' => $catalog,
            'selectedType' => $selectedType,
            'selectedVariants' => $this->variantsForSelectedType(),
            'lineRows' => $this->lineRows(),
        ]);
    }

    private function canAdd(): bool
    {
        return $this->event()->status !== LogisticsEventStatus::CANCELLED
            && app(WarehouseService::class)->all()->count() >= 2;
    }

    private function emptyHint(): string
    {
        return match ($this->event()->type) {
            LogisticsEventType::RETURN => 'Brak przemieszczeń powiązanych z tym zjazdem.',
            LogisticsEventType::TRANSFER => 'Brak przemieszczeń powiązanych z tym transferem.',
            default => 'Brak przemieszczeń powiązanych z tym wyjazdem.',
        };
    }

    /**
     * @return Collection<int, array{from: string, to: string, notes: ?string, creator: ?string, happened_at: string, total_qty: int, lines: list<array{name: string, quantity: int, href: ?string}>}>
     */
    private function transferGroups(): Collection
    {
        $movements = EquipmentStockMovement::query()
            ->where('logistics_event_id', $this->logisticsEventId)
            ->whereIn('type', [StockMovementType::TRANSFER_OUT, StockMovementType::TRANSFER_IN])
            ->with([
                'warehouse.location',
                'relatedWarehouse.location',
                'variant.equipment',
                'equipment',
                'creator',
            ])
            ->orderByDesc('id')
            ->get();

        return $movements
            ->groupBy(fn (EquipmentStockMovement $movement) => $movement->batch_id ?: 'movement-'.$movement->id)
            ->map(function (Collection $group) {
                $outbound = $group->where('type', StockMovementType::TRANSFER_OUT)->values();
                $first = $outbound->first() ?? $group->first();
                $from = $first->warehouse;
                $to = $first->relatedWarehouse ?? $group->firstWhere('type', StockMovementType::TRANSFER_IN)?->warehouse;

                return [
                    'from' => $from?->display_name ?? '—',
                    'to' => $to?->display_name ?? '—',
                    'notes' => $first->notes,
                    'creator' => $first->creator?->name,
                    'happened_at' => $first->happenedAtLabel(),
                    'total_qty' => (int) $outbound->sum('quantity'),
                    'lines' => $outbound->map(fn (EquipmentStockMovement $movement) => [
                        'name' => $movement->variant?->display_name ?? $movement->equipment?->name ?? '—',
                        'quantity' => (int) $movement->quantity,
                        'href' => $movement->equipment_id
                            ? route('equipment.show', [
                                'equipment' => $movement->equipment_id,
                                'warehouse_id' => $from?->id ?? $movement->warehouse_id,
                            ])
                            : null,
                    ])->values()->all(),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, Equipment>
     */
    private function equipmentMatches(): Collection
    {
        $catalog = $this->catalog();
        $term = trim($this->equipmentSearch);
        if ($term === '') {
            return collect();
        }

        $needle = mb_strtolower($term);

        return $catalog
            ->filter(fn (Equipment $type) => str_contains(mb_strtolower($type->name), $needle))
            ->take(12)
            ->values();
    }

    /**
     * @return Collection<int, Equipment>
     */
    private function catalog(): Collection
    {
        return $this->catalogCache ??= tap(
            Equipment::query()
                ->active()
                ->withWarehouseInventory($this->fromWarehouse())
                ->orderBy('name')
                ->get(),
            function (Collection $types) {
                $types->each(function (Equipment $type) {
                    $type->variants->each->setRelation('equipment', $type);
                });
            }
        );
    }

    /**
     * @return Collection<int, EquipmentVariant>
     */
    private function variantsForSelectedType(): Collection
    {
        if (! $this->addEquipmentId) {
            return collect();
        }

        $type = $this->catalog()->firstWhere('id', (int) $this->addEquipmentId);

        return $type?->variants ?? collect();
    }

    /**
     * @return array<int, array{index: int, variant: EquipmentVariant, quantity: int}>
     */
    private function lineRows(): array
    {
        $variants = $this->catalog()->pluck('variants')->flatten()->keyBy('id');
        $rows = [];

        foreach ($this->lines as $index => $line) {
            $variant = $variants->get((int) $line['variant_id']);
            if (! $variant) {
                continue;
            }
            $rows[] = [
                'index' => $index,
                'variant' => $variant,
                'quantity' => (int) $line['quantity'],
            ];
        }

        return $rows;
    }

    private function event(): LogisticsEvent
    {
        return LogisticsEvent::query()->findOrFail($this->logisticsEventId);
    }

    private function fromWarehouse(): Warehouse
    {
        return Warehouse::query()->with('location')->findOrFail($this->warehouseId);
    }

    private function otherWarehouseId(int $exceptId): ?int
    {
        return app(WarehouseService::class)->all()
            ->first(fn (Warehouse $warehouse) => $warehouse->id !== $exceptId)
            ?->id;
    }
}
