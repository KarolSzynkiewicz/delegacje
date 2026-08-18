<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentVariant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class WarehouseIssueForm extends Component
{
    public int $warehouseId;

    /** @var list<int> */
    public array $employeeIds = [];

    public string $issueDate = '';

    public ?string $notes = null;

    public ?int $expandedTypeId = null;

    public ?int $sizePanelTypeId = null;

    /**
     * @var array<int, array{type_id: int, kind: string, assignments: list<array{employee_id: int, variant_id: int|null, quantity: int}>}>
     */
    public array $lines = [];

    public bool $confirming = false;

    public ?int $assigneeId = null;

    public function mount(?Warehouse $warehouse = null): void
    {
        $this->warehouseId = ($warehouse && $warehouse->exists)
            ? $warehouse->id
            : app(WarehouseService::class)->current()->id;
        $this->issueDate = now()->toDateString();
        $this->assigneeId = auth()->id();
        app(WarehouseService::class)->remember($this->warehouse());
    }

    public function updatedWarehouseId(): void
    {
        $this->lines = [];
        $this->expandedTypeId = null;
        $this->sizePanelTypeId = null;
        $this->confirming = false;
        $this->resetErrorBag();
        app(WarehouseService::class)->remember($this->warehouse());
    }

    #[On('employees-updated')]
    #[On('warehouse-issue-employees-updated')]
    public function onEmployeesUpdated(array $employeeIds): void
    {
        $flat = isset($employeeIds[0]) || $employeeIds === []
            ? $employeeIds
            : ($employeeIds['employeeIds'] ?? array_values($employeeIds));

        $this->employeeIds = array_values(array_unique(array_map(
            'intval',
            array_filter($flat, 'is_numeric')
        )));
        $this->syncAssignmentsForEmployees();
    }

    public function toggleType(int $typeId): void
    {
        $this->expandedTypeId = $this->expandedTypeId === $typeId ? null : $typeId;
    }

    public function addTypeToCart(int $typeId, ?string $kind = null): void
    {
        $this->resetErrorBag();

        if ($this->selectedEmployeeIds() === []) {
            $this->addError('employeeIds', 'Najpierw wybierz pracowników.');

            return;
        }

        $type = $this->typeFromCatalog($typeId);
        if (! $type || ! $type->issuable) {
            $this->addError('lines', 'Tej pozycji nie można wydać z tego magazynu.');

            return;
        }

        $kind = $this->kindFor($type);

        if ($type->hasVariants()) {
            $this->ensureTypeGroup($type, $kind);
            $this->ensurePeopleOnGroup($type);
            $this->openSizePanel($typeId);

            return;
        }

        $variant = $type->variants->first();
        if (! $variant) {
            return;
        }

        $this->addToCart($variant->id, $kind);
    }

    public function addToCart(int $variantId, ?string $kind = null, int $quantity = 1): void
    {
        $this->resetErrorBag();

        $employeeIds = $this->selectedEmployeeIds();
        if ($employeeIds === []) {
            $this->addError('employeeIds', 'Najpierw wybierz pracowników.');

            return;
        }

        $variant = $this->variantFromCatalog($variantId);
        if (! $variant || ! $variant->equipment?->issuable) {
            $this->addError('lines', 'Tej pozycji nie można wydać z tego magazynu.');

            return;
        }

        $type = $variant->equipment;
        $kind = $this->kindFor($type);
        $quantity = max(1, $quantity);
        $needed = $quantity * count($employeeIds);
        $remaining = $this->remainingFor($variantId);
        if ($needed > $remaining) {
            $this->addError('lines', "Niewystarczająca ilość w magazynie. Dostępne: {$remaining}.");

            return;
        }

        $this->ensureTypeGroup($type, $kind);

        foreach ($employeeIds as $employeeId) {
            $this->putAssignment($type->id, $employeeId, $variantId, $quantity);
        }

        if ($type->hasVariants()) {
            $this->openSizePanel($type->id);
        }
    }

    public function openSizePanel(int $typeId): void
    {
        $this->resetErrorBag('sizePanel');
        $this->sizePanelTypeId = $typeId;
        $this->prefillLastSizes($typeId);
    }

    public function closeSizePanel(): void
    {
        $this->resetErrorBag('sizePanel');
        $this->sizePanelTypeId = null;
    }

    public function confirmSizePanel(): void
    {
        $this->resetErrorBag('sizePanel');

        foreach ($this->sizePanelProblems() as $message) {
            $this->addError('sizePanel', $message);
        }

        if ($this->getErrorBag()->has('sizePanel')) {
            return;
        }

        $this->sizePanelTypeId = null;
    }

    public function setAssignmentVariant(int $typeId, int $employeeId, $variantId): void
    {
        $this->resetErrorBag();
        $variantId = $variantId === '' || $variantId === null ? null : (int) $variantId;

        $index = $this->groupIndex($typeId);
        if ($index === null) {
            return;
        }

        foreach ($this->lines[$index]['assignments'] as $key => $assignment) {
            if ((int) $assignment['employee_id'] !== $employeeId) {
                continue;
            }

            $this->lines[$index]['assignments'][$key]['variant_id'] = $variantId;
        }
    }

    public function setAssignmentQuantity(int $typeId, int $employeeId, $quantity): void
    {
        $this->resetErrorBag();
        $index = $this->groupIndex($typeId);
        if ($index === null) {
            return;
        }

        $quantity = (int) $quantity;
        if ($quantity < 1) {
            $this->removeAssignment($typeId, $employeeId);

            return;
        }

        foreach ($this->lines[$index]['assignments'] as $key => $assignment) {
            if ((int) $assignment['employee_id'] !== $employeeId) {
                continue;
            }

            $this->lines[$index]['assignments'][$key]['quantity'] = $quantity;
        }
    }

    public function applyVariantToAll(int $typeId, $variantId): void
    {
        $variantId = $variantId === '' || $variantId === null ? null : (int) $variantId;
        if ($variantId === null) {
            return;
        }

        $index = $this->groupIndex($typeId);
        if ($index === null) {
            return;
        }

        foreach ($this->lines[$index]['assignments'] as $assignment) {
            $this->setAssignmentVariant($typeId, (int) $assignment['employee_id'], $variantId);
        }
    }

    public function updateLineQuantity(int $index, $quantity): void
    {
        if (! isset($this->lines[$index]['assignments'][0])) {
            return;
        }

        $group = $this->lines[$index];
        $assignment = $group['assignments'][0];
        $this->setAssignmentQuantity((int) $group['type_id'], (int) $assignment['employee_id'], $quantity);
    }

    public function removeFromCart(int $variantId): void
    {
        $variant = $this->variantFromCatalog($variantId);
        if ($variant) {
            $this->removeTypeFromCart($variant->equipment_id);

            return;
        }

        foreach ($this->lines as $group) {
            foreach ($group['assignments'] as $assignment) {
                if ((int) ($assignment['variant_id'] ?? 0) === $variantId) {
                    $this->removeTypeFromCart((int) $group['type_id']);

                    return;
                }
            }
        }
    }

    public function removeTypeFromCart(int $typeId): void
    {
        $this->lines = collect($this->lines)
            ->reject(fn ($group) => (int) $group['type_id'] === $typeId)
            ->values()
            ->all();

        if ($this->sizePanelTypeId === $typeId) {
            $this->sizePanelTypeId = null;
        }
    }

    public function removeLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $this->removeTypeFromCart((int) $this->lines[$index]['type_id']);
    }

    public function save(EquipmentService $equipmentService)
    {
        $this->prepare();
        if (! $this->confirming) {
            return;
        }

        return $this->confirm($equipmentService);
    }

    public function prepare(): void
    {
        $this->sizePanelTypeId = null;
        $entries = $this->validatedEntries();
        if ($entries === null) {
            $this->confirming = false;

            return;
        }

        $this->confirming = true;
    }

    public function backToEdit(): void
    {
        $this->confirming = false;
    }

    public function confirm(EquipmentService $equipmentService)
    {
        if (! $this->confirming) {
            $this->prepare();

            return;
        }

        $entries = $this->validatedEntries();
        if ($entries === null) {
            $this->confirming = false;

            return;
        }

        $this->validate([
            'assigneeId' => 'required|exists:users,id',
        ], [
            'assigneeId.required' => 'Wybierz osobę, która ma skompletować to wydanie.',
            'assigneeId.exists' => 'Wybrany użytkownik nie istnieje.',
        ]);

        try {
            $issues = $equipmentService->issueSession(
                $entries,
                $this->warehouse(),
                Carbon::parse($this->issueDate),
                filled($this->notes) ? $this->notes : null,
                $this->assigneeId,
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());
            $this->confirming = false;

            return;
        }

        $dispatch = $issues->first()?->dispatch;
        $label = $dispatch?->number ?? $issues->count().' '.$this->positionsLabel($issues->count());
        session()->flash('success', "Zapisano zlecenie {$label}.");

        return $this->redirect(route('equipment.tab.orders', ['warehouse_id' => $this->warehouseId]), navigate: false);
    }

    public function remainingFor(int $variantId): int
    {
        return max(0, $this->stockFor($variantId) - $this->quantityInCart($variantId));
    }

    public function quantityInCart(int $variantId): int
    {
        return (int) collect($this->lines)->sum(function ($group) use ($variantId) {
            return collect($group['assignments'] ?? [])
                ->filter(fn ($assignment) => (int) ($assignment['variant_id'] ?? 0) === $variantId)
                ->sum(fn ($assignment) => (int) $assignment['quantity']);
        });
    }

    public function render()
    {
        $lanes = $this->stockCards();
        $cart = $this->cartCards();

        return view('livewire.warehouse-issue-form', [
            'warehouses' => app(WarehouseService::class)->all(),
            'stockCards' => $lanes,
            'cartCards' => $cart,
            'sizePanel' => $this->confirming ? null : $this->sizePanelView(),
            'multipleRecipients' => count($this->selectedEmployeeIds()) > 1,
            'confirming' => $this->confirming,
            'preview' => $this->confirming ? $this->previewSummary() : null,
            'assignees' => User::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * @return Collection<int, Equipment>
     */
    private function catalog(): Collection
    {
        return tap(
            Equipment::query()
                ->active()
                ->issuable()
                ->withWarehouseInventory($this->warehouse())
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
     * @return list<array{type: Equipment, has_variants: bool, remaining: int, stock: int, variants: list<array{variant: EquipmentVariant, remaining: int, stock: int}>}>
     */
    private function stockCards(): array
    {
        $warehouse = $this->warehouse();
        $cards = [];

        foreach ($this->catalog() as $type) {
            $variants = [];
            $remainingTotal = 0;
            $stockTotal = 0;

            foreach ($type->variants as $variant) {
                $stock = $variant->availableIn($warehouse);
                $remaining = max(0, $stock - $this->quantityInCart($variant->id));
                $variants[] = [
                    'variant' => $variant,
                    'remaining' => $remaining,
                    'stock' => $stock,
                ];
                $remainingTotal += $remaining;
                $stockTotal += $stock;
            }

            $cards[] = [
                'type' => $type,
                'has_variants' => $type->hasVariants(),
                'remaining' => $remainingTotal,
                'stock' => $stockTotal,
                'variants' => $variants,
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{index: int, type: Equipment, has_variants: bool, quantity: int, filled: int, total: int, variant_badges: list<array{label: string, count: int, recipients: list<string>}>, assignments: list<array{employee_id: int, variant_id: int|null, quantity: int}>}>
     */
    private function cartCards(): array
    {
        $types = $this->catalog()->keyBy('id');
        $cards = [];

        foreach ($this->lines as $index => $group) {
            $type = $types->get((int) $group['type_id']);
            if (! $type) {
                continue;
            }

            $assignments = $group['assignments'] ?? [];
            $filled = collect($assignments)->filter(fn ($assignment) => (int) ($assignment['variant_id'] ?? 0) > 0)->count();

            $cards[] = [
                'index' => $index,
                'type' => $type,
                'has_variants' => $type->hasVariants(),
                'quantity' => (int) collect($assignments)->sum(fn ($assignment) => (int) $assignment['quantity']),
                'filled' => $filled,
                'total' => count($assignments),
                'variant_badges' => $this->variantBadges($type, $assignments),
                'assignments' => $assignments,
            ];
        }

        return $cards;
    }

    /**
     * @return array{type: Equipment, variants: Collection<int, EquipmentVariant>, assignments: list<array{employee: Employee, variant_id: int|null, quantity: int, last_label: ?string, last_variant_id: int|null}>, stock: list<array{variant: EquipmentVariant, remaining: int, stock: int, requested: int, over: bool}>, missing: int, shortages: list<array{label: string, requested: int, stock: int}>}|null
     */
    private function sizePanelView(): ?array
    {
        if (! $this->sizePanelTypeId) {
            return null;
        }

        $type = $this->typeFromCatalog($this->sizePanelTypeId);
        $index = $this->groupIndex($this->sizePanelTypeId);
        if (! $type || $index === null) {
            return null;
        }

        $employees = $this->selectedEmployees()->keyBy('id');
        $lastByEmployee = $this->lastVariantLabels($type->id, $employees->keys()->all());
        $warehouse = $this->warehouse();

        $assignments = [];
        foreach ($this->lines[$index]['assignments'] as $assignment) {
            $employee = $employees->get((int) $assignment['employee_id']);
            if (! $employee) {
                continue;
            }
            $last = $lastByEmployee[(int) $employee->id] ?? null;
            $assignments[] = [
                'employee' => $employee,
                'variant_id' => $assignment['variant_id'] ? (int) $assignment['variant_id'] : null,
                'quantity' => (int) $assignment['quantity'],
                'last_label' => $last['label'] ?? null,
                'last_variant_id' => $last['variant_id'] ?? null,
            ];
        }

        $stock = $type->variants->map(function (EquipmentVariant $variant) use ($warehouse) {
            $qty = $variant->availableIn($warehouse);
            $requested = $this->quantityInCart($variant->id);

            return [
                'variant' => $variant,
                'remaining' => $qty - $requested,
                'stock' => $qty,
                'requested' => $requested,
                'over' => $requested > $qty,
            ];
        })->values();

        return [
            'type' => $type,
            'variants' => $type->variants,
            'assignments' => $assignments,
            'stock' => $stock,
            'missing' => collect($assignments)->filter(fn (array $row) => ! $row['variant_id'])->count(),
            'shortages' => $stock
                ->filter(fn (array $option) => $option['over'])
                ->map(fn (array $option) => [
                    'label' => $option['variant']->kind_label,
                    'requested' => $option['requested'],
                    'stock' => $option['stock'],
                ])
                ->values()
                ->all(),
        ];
    }

    private function typeFromCatalog(int $typeId): ?Equipment
    {
        return $this->catalog()->firstWhere('id', $typeId);
    }

    private function variantFromCatalog(int $variantId): ?EquipmentVariant
    {
        return $this->catalog()->pluck('variants')->flatten()->firstWhere('id', $variantId);
    }

    private function stockFor(int $variantId): int
    {
        return $this->variantFromCatalog($variantId)?->availableIn($this->warehouse()) ?? 0;
    }

    /**
     * @return list<array{employee_id: int, variant_id: int, quantity: int}>|null
     */
    private function validatedEntries(): ?array
    {
        $this->syncEmployeeIds();

        $this->validate([
            'employeeIds' => 'required|array|min:1',
            'employeeIds.*' => 'exists:employees,id',
            'issueDate' => 'required|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
        ], [
            'employeeIds.required' => 'Wybierz co najmniej jednego pracownika.',
            'employeeIds.min' => 'Wybierz co najmniej jednego pracownika.',
            'lines.required' => 'Przerzuć co najmniej jedną pozycję na prawą stronę.',
            'lines.min' => 'Przerzuć co najmniej jedną pozycję na prawą stronę.',
        ], [
            'employeeIds' => 'pracownik',
            'issueDate' => 'data wydania',
        ]);

        $entries = $this->flattenEntries();
        if ($entries === null) {
            return null;
        }

        try {
            $this->assertLinesMatchKind();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return null;
        }

        return $entries;
    }

    /**
     * @return array{warehouse: string, issue_date: string, notes: ?string, people_count: int, position_count: int, recipients: list<array{name: string, lines: list<array{item: string, variant: string, quantity: int, kind: string}>}>}|null
     */
    private function previewSummary(): ?array
    {
        $entries = $this->flattenEntries();
        if ($entries === null) {
            return null;
        }

        $employees = $this->selectedEmployees()->keyBy('id');
        $recipients = [];

        foreach ($entries as $entry) {
            $employee = $employees->get($entry['employee_id']);
            $variant = $this->variantFromCatalog($entry['variant_id']);
            if (! $employee || ! $variant) {
                continue;
            }

            $id = $employee->id;
            if (! isset($recipients[$id])) {
                $recipients[$id] = [
                    'name' => $employee->full_name,
                    'lines' => [],
                ];
            }

            $recipients[$id]['lines'][] = [
                'item' => $variant->equipment?->name ?? $variant->display_name,
                'variant' => $variant->kind_label,
                'quantity' => $entry['quantity'],
                'kind' => $variant->equipment?->returnable ? 'Do zwrotu' : 'Bezzwrotne',
            ];
        }

        return [
            'warehouse' => $this->warehouse()->display_name,
            'issue_date' => Carbon::parse($this->issueDate)->format('d.m.Y'),
            'notes' => filled($this->notes) ? $this->notes : null,
            'people_count' => count($recipients),
            'position_count' => count($entries),
            'recipients' => array_values($recipients),
        ];
    }

    /**
     * @return list<array{employee_id: int, variant_id: int, quantity: int}>|null
     */
    private function flattenEntries(): ?array
    {
        $entries = [];

        foreach ($this->lines as $group) {
            foreach ($group['assignments'] as $assignment) {
                $variantId = (int) ($assignment['variant_id'] ?? 0);
                if ($variantId < 1) {
                    $type = $this->typeFromCatalog((int) $group['type_id']);
                    $this->addError('lines', 'Uzupełnij rozmiary dla „'.($type?->name ?? 'pozycji').'”.');
                    $this->sizePanelTypeId = (int) $group['type_id'];

                    return null;
                }

                $entries[] = [
                    'employee_id' => (int) $assignment['employee_id'],
                    'variant_id' => $variantId,
                    'quantity' => (int) $assignment['quantity'],
                ];
            }
        }

        if ($entries === []) {
            $this->addError('lines', 'Przerzuć co najmniej jedną pozycję na prawą stronę.');

            return null;
        }

        if (! $this->assertStockForEntries($entries)) {
            return null;
        }

        return $entries;
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::query()->with('location')->findOrFail($this->warehouseId);
    }

    /**
     * @return list<int>
     */
    private function selectedEmployeeIds(): array
    {
        return collect($this->employeeIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Employee>
     */
    private function selectedEmployees(): Collection
    {
        $ids = $this->selectedEmployeeIds();
        if ($ids === []) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $ids)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    private function syncEmployeeIds(): void
    {
        $this->employeeIds = $this->selectedEmployeeIds();
    }

    private function ensureTypeGroup(Equipment $type, string $kind): int
    {
        $index = $this->groupIndex($type->id);
        if ($index === null) {
            $this->lines[] = [
                'type_id' => $type->id,
                'kind' => $kind,
                'assignments' => [],
            ];
            $index = count($this->lines) - 1;
        }

        return $index;
    }

    private function ensurePeopleOnGroup(Equipment $type): void
    {
        $index = $this->groupIndex($type->id);
        if ($index === null) {
            return;
        }

        foreach ($this->selectedEmployeeIds() as $employeeId) {
            $exists = collect($this->lines[$index]['assignments'])
                ->contains(fn ($assignment) => (int) $assignment['employee_id'] === $employeeId);
            if (! $exists) {
                $this->lines[$index]['assignments'][] = [
                    'employee_id' => $employeeId,
                    'variant_id' => $type->hasVariants() ? null : $type->variants->first()?->id,
                    'quantity' => 1,
                ];
            }
        }
    }

    private function putAssignment(int $typeId, int $employeeId, int $variantId, int $quantity): void
    {
        $index = $this->groupIndex($typeId);
        if ($index === null) {
            return;
        }

        foreach ($this->lines[$index]['assignments'] as $key => $assignment) {
            if ((int) $assignment['employee_id'] !== $employeeId) {
                continue;
            }

            $current = $assignment['variant_id'] ? (int) $assignment['variant_id'] : null;
            if ($current === $variantId) {
                $this->lines[$index]['assignments'][$key]['quantity'] = (int) $assignment['quantity'] + $quantity;
            } else {
                $this->lines[$index]['assignments'][$key]['variant_id'] = $variantId;
                $this->lines[$index]['assignments'][$key]['quantity'] = $quantity;
            }

            return;
        }

        $this->lines[$index]['assignments'][] = [
            'employee_id' => $employeeId,
            'variant_id' => $variantId,
            'quantity' => $quantity,
        ];
    }

    private function removeAssignment(int $typeId, int $employeeId): void
    {
        $index = $this->groupIndex($typeId);
        if ($index === null) {
            return;
        }

        $this->lines[$index]['assignments'] = collect($this->lines[$index]['assignments'])
            ->reject(fn ($assignment) => (int) $assignment['employee_id'] === $employeeId)
            ->values()
            ->all();

        if ($this->lines[$index]['assignments'] === []) {
            $this->removeTypeFromCart($typeId);
        }
    }

    private function groupIndex(int $typeId): ?int
    {
        foreach ($this->lines as $index => $group) {
            if ((int) $group['type_id'] === $typeId) {
                return $index;
            }
        }

        return null;
    }

    private function syncAssignmentsForEmployees(): void
    {
        $ids = $this->selectedEmployeeIds();
        if ($ids === []) {
            $this->lines = [];
            $this->sizePanelTypeId = null;

            return;
        }

        foreach ($this->lines as $index => $group) {
            $type = $this->typeFromCatalog((int) $group['type_id']);
            $kept = collect($group['assignments'])
                ->filter(fn ($assignment) => in_array((int) $assignment['employee_id'], $ids, true))
                ->values();

            foreach ($ids as $employeeId) {
                $exists = $kept->contains(fn ($assignment) => (int) $assignment['employee_id'] === $employeeId);
                if (! $exists) {
                    $kept->push([
                        'employee_id' => $employeeId,
                        'variant_id' => ($type && ! $type->hasVariants()) ? $type->variants->first()?->id : null,
                        'quantity' => 1,
                    ]);
                }
            }

            $this->lines[$index]['assignments'] = $kept->all();
        }
    }

    private function prefillLastSizes(int $typeId): void
    {
        $index = $this->groupIndex($typeId);
        $type = $this->typeFromCatalog($typeId);
        if ($index === null || ! $type) {
            return;
        }

        $validIds = $type->variants->pluck('id')->all();

        foreach ($this->lines[$index]['assignments'] as $key => $assignment) {
            if ((int) ($assignment['variant_id'] ?? 0) > 0) {
                continue;
            }

            $lastId = EquipmentIssue::query()
                ->where('employee_id', (int) $assignment['employee_id'])
                ->where('equipment_id', $typeId)
                ->latest('id')
                ->value('equipment_variant_id');

            if ($lastId && in_array((int) $lastId, $validIds, true) && $this->remainingFor((int) $lastId) > 0) {
                $this->lines[$index]['assignments'][$key]['variant_id'] = (int) $lastId;
            }
        }
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, array{label: string, variant_id: int}>
     */
    private function lastVariantLabels(int $typeId, array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $issues = EquipmentIssue::query()
            ->where('equipment_id', $typeId)
            ->whereIn('employee_id', $employeeIds)
            ->with('variant')
            ->latest('id')
            ->get()
            ->unique('employee_id');

        $labels = [];
        foreach ($issues as $issue) {
            if ($issue->variant?->kind_label && $issue->equipment_variant_id) {
                $labels[(int) $issue->employee_id] = [
                    'label' => 'ostatnio '.$issue->variant->kind_label,
                    'variant_id' => (int) $issue->equipment_variant_id,
                ];
            }
        }

        return $labels;
    }

    /**
     * @param  list<array{employee_id: int, variant_id: int|null, quantity: int}>  $assignments
     * @return list<array{label: string, count: int, recipients: list<string>}>
     */
    private function variantBadges(Equipment $type, array $assignments): array
    {
        $employees = $this->selectedEmployees()->keyBy('id');

        if (! $type->hasVariants()) {
            $names = collect($assignments)
                ->map(fn ($assignment) => $employees->get((int) $assignment['employee_id'])?->full_name)
                ->filter()
                ->values()
                ->all();
            $qty = (int) collect($assignments)->sum(fn ($assignment) => (int) $assignment['quantity']);

            return [[
                'label' => count($assignments) === 1 ? '1 os.' : count($assignments).' os.',
                'count' => $qty,
                'recipients' => $names,
            ]];
        }

        $variants = $type->variants->keyBy('id');

        return collect($assignments)
            ->filter(fn ($assignment) => (int) ($assignment['variant_id'] ?? 0) > 0)
            ->groupBy(fn ($assignment) => (int) $assignment['variant_id'])
            ->map(function (Collection $group, $variantId) use ($variants, $employees) {
                $names = $group
                    ->map(fn ($assignment) => $employees->get((int) $assignment['employee_id'])?->full_name)
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'label' => $variants->get((int) $variantId)?->kind_label ?? '?',
                    'count' => (int) $group->sum(fn ($assignment) => (int) $assignment['quantity']),
                    'recipients' => $names,
                ];
            })
            ->values()
            ->all();
    }

    private function kindFor(Equipment $type): string
    {
        return $type->returnable ? 'returnable' : 'given';
    }

    /**
     * @return list<string>
     */
    private function sizePanelProblems(?int $typeId = null): array
    {
        $typeId = $typeId ?? $this->sizePanelTypeId;
        $index = $typeId ? $this->groupIndex($typeId) : null;
        $type = $typeId ? $this->typeFromCatalog($typeId) : null;
        if ($index === null || ! $type) {
            return ['Nie można zapisać rozmiarów.'];
        }

        $messages = [];
        $requested = [];

        foreach ($this->lines[$index]['assignments'] as $assignment) {
            $variantId = (int) ($assignment['variant_id'] ?? 0);
            if ($variantId < 1) {
                $messages[] = 'Uzupełnij rozmiar dla każdej osoby.';

                break;
            }

            $requested[$variantId] = ($requested[$variantId] ?? 0) + (int) $assignment['quantity'];
        }

        foreach ($requested as $variantId => $quantity) {
            $available = $this->stockFor((int) $variantId);
            if ($quantity <= $available) {
                continue;
            }

            $label = $this->variantFromCatalog((int) $variantId)?->kind_label ?? 'wybrany rozmiar';
            $messages[] = "Rozmiar {$label}: wybrane {$quantity}, dostępne {$available}.";
        }

        return array_values(array_unique($messages));
    }

    /**
     * @param  list<array{employee_id: int, variant_id: int, quantity: int}>  $entries
     */
    private function assertStockForEntries(array $entries): bool
    {
        $needed = collect($entries)
            ->groupBy('variant_id')
            ->map(fn (Collection $group) => (int) $group->sum('quantity'));

        foreach ($needed as $variantId => $quantity) {
            $available = $this->stockFor((int) $variantId);
            if ($quantity <= $available) {
                continue;
            }

            $variant = $this->variantFromCatalog((int) $variantId);
            $this->addError(
                'lines',
                'Niewystarczająca ilość „'.($variant?->display_name ?? 'pozycji')."”. Dostępne: {$available}, wybrane: {$quantity}."
            );

            return false;
        }

        return true;
    }

    private function assertLinesMatchKind(): void
    {
        foreach ($this->lines as $group) {
            $type = $this->typeFromCatalog((int) $group['type_id']);
            $isReturnable = (bool) $type?->returnable;
            $kind = $group['kind'] ?? null;
            if ($type === null || ($kind === 'returnable' && ! $isReturnable) || ($kind === 'given' && $isReturnable)) {
                throw ValidationException::withMessages([
                    'lines' => 'Rodzaj wydania nie zgadza się z kartą produktu.',
                ]);
            }
        }
    }

    private function positionsLabel(int $count): string
    {
        if ($count === 1) {
            return 'pozycję';
        }

        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'pozycje';
        }

        return 'pozycji';
    }
}
