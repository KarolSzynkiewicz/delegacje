<?php

namespace App\Livewire;

use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Livewire\Concerns\PicksConsumptionDestination;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\LogisticsEvent;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class EquipmentStockMovementForm extends Component
{
    use PicksConsumptionDestination;

    public int $equipmentId;

    public int $warehouseId;

    public string $action = '';

    public $variantId = null;

    public int $quantity = 1;

    public string $reason = '';

    public ?string $notes = null;

    public $employeeId = null;

    public string $employeeSearch = '';

    public $targetWarehouseId = null;

    public $logisticsEventId = null;

    public string $logisticsEventSearch = '';

    /** @var array<int, array{variant_id: int, quantity: int|string}> */
    public array $lines = [];

    public function mount(Equipment $equipment, Warehouse $warehouse): void
    {
        $this->equipmentId = $equipment->id;
        $this->warehouseId = $warehouse->id;
        $this->preselectVariant($equipment);
    }

    public function startReceipt(): void
    {
        $this->action = StockMovementType::RECEIPT->value;
        $this->reason = StockMovementReason::Purchase->value;
        $this->lines = $this->defaultLines();
        $this->resetValidation();
    }

    public function startAdjustment(): void
    {
        $this->action = StockMovementType::ADJUSTMENT->value;
        $this->reason = StockMovementReason::InventoryShortage->value;
        $this->resetValidation();
    }

    public function startIssue(): void
    {
        $this->action = 'issue';
        $this->reason = '';
        $this->lines = $this->defaultLines();
        $this->resetValidation();
    }

    public function startTransfer(): void
    {
        $this->action = 'transfer';
        $this->reason = '';
        $this->lines = $this->defaultLines();
        $this->targetWarehouseId = $this->otherWarehouseId((int) $this->warehouseId);
        $this->logisticsEventId = null;
        $this->logisticsEventSearch = '';
        $this->resetValidation();
    }

    public function startConsume(): void
    {
        if ($this->equipment()->issuable) {
            return;
        }

        $this->action = 'consume';
        $this->reason = '';
        $this->lines = $this->defaultLines();
        $this->resetDestination();
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->action = '';
        $this->quantity = 1;
        $this->reason = '';
        $this->notes = null;
        $this->employeeId = null;
        $this->employeeSearch = '';
        $this->targetWarehouseId = null;
        $this->logisticsEventId = null;
        $this->logisticsEventSearch = '';
        $this->lines = [];
        $this->resetDestination();
        $this->resetValidation();
        $this->preselectVariant($this->equipment());
    }

    public function updatedEmployeeSearch(): void
    {
        $selectedName = $this->employeeId
            ? Employee::query()->find($this->employeeId)?->full_name
            : null;

        if ($selectedName !== null && $this->employeeSearch === $selectedName) {
            return;
        }

        $this->employeeId = null;
    }

    public function selectEmployee(int $id): void
    {
        $employee = Employee::query()->find($id);
        if (! $employee) {
            return;
        }

        $this->employeeId = $employee->id;
        $this->employeeSearch = $employee->full_name;
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
        $this->employeeSearch = '';
    }

    public function updatedWarehouseId(): void
    {
        if ($this->action !== 'transfer') {
            return;
        }

        if ((int) $this->targetWarehouseId === (int) $this->warehouseId) {
            $this->targetWarehouseId = $this->otherWarehouseId((int) $this->warehouseId);
        }
    }

    public function updatedLogisticsEventSearch(): void
    {
        $selectedLabel = $this->logisticsEventId
            ? $this->selectedLogisticsEventLabel()
            : null;

        if ($selectedLabel !== null && $this->logisticsEventSearch === $selectedLabel) {
            return;
        }

        $this->logisticsEventId = null;
    }

    public function selectLogisticsEvent(int $id): void
    {
        $event = LogisticsEvent::query()
            ->with(['fromLocation', 'toLocation'])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->find($id);

        if (! $event) {
            return;
        }

        $this->logisticsEventId = $event->id;
        $this->logisticsEventSearch = app(EquipmentService::class)->logisticsEventLabel($event);
    }

    public function clearLogisticsEvent(): void
    {
        $this->logisticsEventId = null;
        $this->logisticsEventSearch = '';
    }

    public function save(EquipmentService $equipmentService)
    {
        $rules = [
            'warehouseId' => 'required|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ];

        if (! $this->usesQuantityTable()) {
            $rules['variantId'] = 'required|integer';
            $rules['quantity'] = 'required|integer|min:1';
        }

        $this->validate($rules);

        $equipment = $this->equipment();
        $warehouse = $this->warehouse();
        $filledLines = $this->usesQuantityTable() ? $this->filledLines() : [];

        try {
            if ($this->action === StockMovementType::RECEIPT->value) {
                $this->validate([
                    'reason' => [
                        'required',
                        'string',
                        Rule::in(array_map(
                            fn (StockMovementReason $reason) => $reason->value,
                            StockMovementReason::forType(StockMovementType::RECEIPT)
                        )),
                    ],
                ]);

                if ($filledLines === []) {
                    $this->addError('lines', 'Podaj ilość przy co najmniej jednym wariancie.');

                    return;
                }

                $validIds = $equipment->variants->pluck('id');
                foreach ($filledLines as $line) {
                    if (! $validIds->contains($line['variant_id'])) {
                        $this->addError('lines', 'Wybierz wariant tej pozycji.');

                        return;
                    }
                }

                $equipmentService->receiveStock(
                    $warehouse,
                    $filledLines,
                    StockMovementReason::from($this->reason),
                    $this->notes
                );
                $success = 'Przyjęto towar. Stan magazynu wzrósł.';
            } elseif ($this->action === 'issue') {
                $this->validate([
                    'employeeId' => 'required|integer|exists:employees,id',
                ]);

                if ($filledLines === []) {
                    $this->addError('lines', 'Podaj ilość przy co najmniej jednym wariancie.');

                    return;
                }

                $employee = Employee::query()->findOrFail((int) $this->employeeId);
                $equipmentService->issueAndFulfillLines(
                    $employee,
                    $filledLines,
                    $warehouse,
                    $this->notes
                );
                $success = 'Wydano sprzęt. Stan magazynu spadł.';
            } elseif ($this->action === 'transfer') {
                $this->validate([
                    'targetWarehouseId' => 'required|integer|exists:warehouses,id|different:warehouseId',
                    'logisticsEventId' => 'nullable|integer|exists:logistics_events,id',
                ]);

                if ($filledLines === []) {
                    $this->addError('lines', 'Podaj ilość przy co najmniej jednym wariancie.');

                    return;
                }

                $target = Warehouse::query()->findOrFail((int) $this->targetWarehouseId);
                $event = $this->logisticsEventId
                    ? LogisticsEvent::query()->find((int) $this->logisticsEventId)
                    : null;

                $equipmentService->transferStockLines(
                    $warehouse,
                    $target,
                    $filledLines,
                    $event,
                    $this->notes
                );
                $success = 'Przemieszczono sprzęt między magazynami.';
            } elseif ($this->action === 'consume') {
                $this->validate($this->destinationValidationRules(), [
                    'destinationType.required' => 'Wybierz, na co schodzi rozchód.',
                    'destinationId.required' => 'Wskaż konkretne przeznaczenie.',
                ]);

                if ($filledLines === []) {
                    $this->addError('lines', 'Podaj ilość przy co najmniej jednym wariancie.');

                    return;
                }

                $destination = $this->resolveDestination();
                if (! $destination) {
                    $this->addError('destinationId', 'Wskaż konkretne przeznaczenie.');

                    return;
                }

                $equipmentService->consumeItems(
                    $filledLines,
                    $warehouse,
                    $destination,
                    $this->notes
                );
                $success = 'Zaksięgowano rozchód. Stan magazynu spadł.';
            } else {
                $variant = $equipment->variants->firstWhere('id', (int) $this->variantId);
                if (! $variant) {
                    $this->addError('variantId', 'Wybierz wariant tej pozycji.');

                    return;
                }

                $type = StockMovementType::tryFrom($this->action);
                if ($type !== StockMovementType::ADJUSTMENT) {
                    $this->addError('action', 'Wybierz przyjęcie, wydanie, przemieszczenie albo korektę.');

                    return;
                }

                $this->validate([
                    'reason' => [
                        'required',
                        'string',
                        Rule::in(array_map(
                            fn (StockMovementReason $reason) => $reason->value,
                            StockMovementReason::forType($type)
                        )),
                    ],
                ]);

                $equipmentService->recordStockMovement(
                    $variant,
                    $warehouse,
                    $type,
                    $this->quantity,
                    StockMovementReason::from($this->reason),
                    $this->notes
                );
                $success = 'Zapisano korektę. Stan magazynu spadł.';
            }
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
            if ($e->validator->errors()->has('lines') || $e->validator->errors()->has('receiptLines') || $e->validator->errors()->has('quantity')) {
                $this->addError('lines', $e->validator->errors()->first('lines')
                    ?: $e->validator->errors()->first('receiptLines')
                    ?: $e->validator->errors()->first('quantity'));
            }
            if ($e->validator->errors()->has('targetWarehouseId')) {
                $this->addError('targetWarehouseId', $e->validator->errors()->first('targetWarehouseId'));
            }

            return;
        }

        session()->flash('success', $success);

        return $this->redirect(route('equipment.show', [
            'equipment' => $equipment,
            'warehouse_id' => $this->warehouseId,
        ]), navigate: false);
    }

    public function render(EquipmentService $equipmentService)
    {
        $equipment = $this->equipment();
        $warehouse = $this->warehouse();
        $warehouses = app(WarehouseService::class)->all();
        $selected = $equipment->variants->firstWhere('id', (int) $this->variantId);
        $targetWarehouse = $this->action === 'transfer' && $this->targetWarehouseId
            ? $warehouses->firstWhere('id', (int) $this->targetWarehouseId)
            : null;

        return view('livewire.equipment-stock-movement-form', [
            'equipment' => $equipment,
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'targetWarehouse' => $targetWarehouse,
            'selectedVariant' => $selected,
            'onHand' => $selected ? $selected->quantityIn($warehouse) : 0,
            'reserved' => $selected ? $selected->reservedIn($warehouse) : 0,
            'available' => $selected ? $selected->availableIn($warehouse) : 0,
            'targetOnHand' => $selected && $targetWarehouse ? $selected->quantityIn($targetWarehouse) : 0,
            'isReceipt' => $this->action === StockMovementType::RECEIPT->value,
            'isAdjustment' => $this->action === StockMovementType::ADJUSTMENT->value,
            'isIssue' => $this->action === 'issue',
            'isTransfer' => $this->action === 'transfer',
            'isConsume' => $this->action === 'consume',
            'canTransfer' => $warehouses->count() >= 2,
            'reasonOptions' => $this->reasonOptions(),
            'employeeMatches' => $this->employeeMatches(),
            'logisticsEventMatches' => $this->logisticsEventMatches()
                ->map(fn (LogisticsEvent $event) => [
                    'id' => $event->id,
                    'label' => $equipmentService->logisticsEventLabel($event),
                ]),
            'selectedLogisticsEventLabel' => $this->selectedLogisticsEventLabel(),
            'usesQuantityTable' => $this->usesQuantityTable(),
            'variantRows' => $this->variantRows($equipment, $warehouse, $targetWarehouse),
            'movementChart' => $equipmentService->stockMovementChart($equipment),
            ...$this->destinationPickerViewData(),
        ]);
    }

    /**
     * @return list<StockMovementReason>
     */
    private function reasonOptions(): array
    {
        $type = StockMovementType::tryFrom($this->action);
        if (! $type) {
            return [];
        }

        return StockMovementReason::forType($type);
    }

    /**
     * @return Collection<int, Employee>
     */
    private function employeeMatches(): Collection
    {
        if ($this->action !== 'issue' || $this->employeeId) {
            return collect();
        }

        $term = trim($this->employeeSearch);
        if ($term === '') {
            return collect();
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

        return Employee::query()
            ->whereNull('terminated_at')
            ->where(function ($employees) use ($like) {
                $employees->whereRaw('LOWER(COALESCE(first_name, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(last_name, \'\')) LIKE ?', [$like])
                    ->orWhereRaw(
                        'LOWER(TRIM(CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')))) LIKE ?',
                        [$like]
                    );
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(12)
            ->get();
    }

    /**
     * @return Collection<int, LogisticsEvent>
     */
    private function logisticsEventMatches(): Collection
    {
        if ($this->action !== 'transfer' || $this->logisticsEventId) {
            return collect();
        }

        $term = trim($this->logisticsEventSearch);
        if ($term === '') {
            return collect();
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';
        $matchingTypes = collect(LogisticsEventType::cases())
            ->filter(fn (LogisticsEventType $type) => str_contains(mb_strtolower($type->label()), mb_strtolower($term)))
            ->map(fn (LogisticsEventType $type) => $type->value)
            ->values()
            ->all();

        return LogisticsEvent::query()
            ->with(['fromLocation', 'toLocation'])
            ->where('status', '!=', LogisticsEventStatus::CANCELLED)
            ->where(function ($events) use ($term, $like, $matchingTypes) {
                if (ctype_digit($term)) {
                    $events->orWhere('id', (int) $term);
                }

                $events->orWhereHas('fromLocation', fn ($locations) => $locations->whereRaw('LOWER(name) LIKE ?', [$like]))
                    ->orWhereHas('toLocation', fn ($locations) => $locations->whereRaw('LOWER(name) LIKE ?', [$like]));

                if ($matchingTypes !== []) {
                    $events->orWhereIn('type', $matchingTypes);
                }
            })
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get();
    }

    private function selectedLogisticsEventLabel(): ?string
    {
        if (! $this->logisticsEventId) {
            return null;
        }

        $event = LogisticsEvent::query()
            ->with(['fromLocation', 'toLocation'])
            ->find((int) $this->logisticsEventId);

        return $event ? app(EquipmentService::class)->logisticsEventLabel($event) : null;
    }

    private function otherWarehouseId(int $exceptId): ?int
    {
        return app(WarehouseService::class)->all()
            ->first(fn (Warehouse $warehouse) => $warehouse->id !== $exceptId)
            ?->id;
    }

    private function equipment(): Equipment
    {
        $equipment = Equipment::query()
            ->withWarehouseInventory($this->warehouse())
            ->findOrFail($this->equipmentId);

        $equipment->variants->each->setRelation('equipment', $equipment);

        return $equipment;
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::query()->with('location')->findOrFail($this->warehouseId);
    }

    private function preselectVariant(Equipment $equipment): void
    {
        $equipment->loadMissing('variants');
        if ($equipment->variants->count() === 1) {
            $this->variantId = $equipment->variants->first()->id;
        }
    }

    private function usesQuantityTable(): bool
    {
        return in_array($this->action, [
            StockMovementType::RECEIPT->value,
            'issue',
            'transfer',
            'consume',
        ], true);
    }

    /**
     * @return list<array{variant_id: int, quantity: string}>
     */
    private function defaultLines(): array
    {
        return $this->equipment()->variants
            ->map(fn ($variant) => [
                'variant_id' => $variant->id,
                'quantity' => '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{variant_id: int, quantity: int}>
     */
    private function filledLines(): array
    {
        return collect($this->lines)
            ->map(fn (array $line) => [
                'variant_id' => (int) ($line['variant_id'] ?? 0),
                'quantity' => (int) ($line['quantity'] ?? 0),
            ])
            ->filter(fn (array $line) => $line['variant_id'] > 0 && $line['quantity'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<array{index: int, label: string, on_hand: int, reserved: int, available: int, target_on_hand: ?int}>
     */
    private function variantRows(Equipment $equipment, Warehouse $warehouse, ?Warehouse $targetWarehouse): array
    {
        $indexByVariant = collect($this->lines)
            ->mapWithKeys(fn (array $line, int $index) => [(int) ($line['variant_id'] ?? 0) => $index]);

        return $equipment->variants
            ->map(function ($variant) use ($equipment, $warehouse, $targetWarehouse, $indexByVariant) {
                $index = $indexByVariant->get($variant->id);
                if ($index === null) {
                    return null;
                }

                return [
                    'index' => $index,
                    'label' => $equipment->hasVariants() ? $variant->kind_label : $equipment->name,
                    'on_hand' => $variant->quantityIn($warehouse),
                    'reserved' => $variant->reservedIn($warehouse),
                    'available' => $variant->availableIn($warehouse),
                    'target_on_hand' => $targetWarehouse ? $variant->quantityIn($targetWarehouse) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
