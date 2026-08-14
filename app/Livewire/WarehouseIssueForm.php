<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentVariant;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class WarehouseIssueForm extends Component
{
    public int $warehouseId;

    public $employeeId = null;

    public string $employeeSearch = '';

    public string $issueDate = '';

    public ?string $notes = null;

    public ?int $expandedTypeId = null;

    /** @var array<int, array{variant_id: int, quantity: int, kind: string}> */
    public array $lines = [];

    public ?string $flashMessage = null;

    public function mount(?Warehouse $warehouse = null): void
    {
        $this->warehouseId = ($warehouse && $warehouse->exists)
            ? $warehouse->id
            : app(WarehouseService::class)->current()->id;
        $this->issueDate = now()->toDateString();
        app(WarehouseService::class)->remember($this->warehouse());
    }

    public function updatedWarehouseId(): void
    {
        $this->lines = [];
        $this->expandedTypeId = null;
        $this->resetErrorBag();
        app(WarehouseService::class)->remember($this->warehouse());
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
        $employee = Employee::query()->whereNull('terminated_at')->find($id);
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

    public function toggleType(int $typeId): void
    {
        $this->expandedTypeId = $this->expandedTypeId === $typeId ? null : $typeId;
    }

    public function addToCart(int $variantId, string $kind, int $quantity = 1): void
    {
        $this->resetErrorBag();
        $this->flashMessage = null;

        if (! in_array($kind, ['returnable', 'given'], true)) {
            return;
        }

        $variant = $this->variantFromCatalog($variantId);
        if (! $variant || ! $variant->equipment?->issuable) {
            $this->addError('lines', 'Tej pozycji nie można wydać z tego magazynu.');

            return;
        }

        $isReturnable = (bool) $variant->equipment->returnable;
        if ($kind === 'returnable' && ! $isReturnable) {
            $this->addError('lines', '„'.$variant->display_name.'” jest bezzwrotna — upuść po prawej w „Do wydania bezzwrotnie”.');

            return;
        }
        if ($kind === 'given' && $isReturnable) {
            $this->addError('lines', '„'.$variant->display_name.'” jest zwracalna — upuść po prawej w „Do zwrotu”.');

            return;
        }

        $quantity = max(1, $quantity);
        $remaining = $this->remainingFor($variantId);
        if ($quantity > $remaining) {
            $this->addError('lines', "Niewystarczająca ilość w magazynie. Dostępne: {$remaining}.");

            return;
        }

        $index = collect($this->lines)->search(
            fn ($line) => (int) $line['variant_id'] === $variantId && $line['kind'] === $kind
        );

        if ($index !== false) {
            $this->lines[$index]['quantity'] = (int) $this->lines[$index]['quantity'] + $quantity;
        } else {
            $this->lines[] = [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'kind' => $kind,
            ];
        }
    }

    public function updateLineQuantity(int $index, $quantity): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $quantity = (int) $quantity;
        $variantId = (int) $this->lines[$index]['variant_id'];
        $otherInCart = $this->quantityInCart($variantId) - (int) $this->lines[$index]['quantity'];
        $max = $this->stockFor($variantId) - $otherInCart;

        if ($quantity < 1) {
            $this->removeLine($index);

            return;
        }

        $this->lines[$index]['quantity'] = min($quantity, max(1, $max));
    }

    public function removeFromCart(int $variantId): void
    {
        $this->lines = collect($this->lines)
            ->reject(fn ($line) => (int) $line['variant_id'] === $variantId)
            ->values()
            ->all();
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(EquipmentService $equipmentService)
    {
        $count = $this->persist($equipmentService);
        if ($count === null) {
            return;
        }

        session()->flash('success', "Wydano {$count} {$this->positionsLabel($count)}.");

        return $this->redirect(route('equipment.tab.issues'), navigate: false);
    }

    public function saveAndNext(EquipmentService $equipmentService): void
    {
        $count = $this->persist($equipmentService);
        if ($count === null) {
            return;
        }

        $employeeName = Employee::query()->find($this->employeeId)?->full_name ?? 'pracownika';
        $this->resetAfterIssue();
        $this->flashMessage = "Wydano {$count} {$this->positionsLabel($count)} dla {$employeeName}. Możesz wydać kolejnej osobie.";
    }

    public function remainingFor(int $variantId): int
    {
        return max(0, $this->stockFor($variantId) - $this->quantityInCart($variantId));
    }

    public function quantityInCart(int $variantId): int
    {
        return (int) collect($this->lines)
            ->filter(fn ($line) => (int) $line['variant_id'] === $variantId)
            ->sum(fn ($line) => (int) $line['quantity']);
    }

    public function render()
    {
        $lanes = $this->stockLanes();
        $cart = $this->cartLanes();

        return view('livewire.warehouse-issue-form', [
            'warehouses' => app(WarehouseService::class)->all(),
            'employeeMatches' => $this->employeeMatches(),
            'returnableStock' => $lanes['returnable'],
            'givenStock' => $lanes['given'],
            'returnableCart' => $cart['returnable'],
            'givenCart' => $cart['given'],
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
     * @return array{returnable: list<array{type: Equipment, has_variants: bool, remaining: int, stock: int, variants: list<array{variant: EquipmentVariant, remaining: int, stock: int}>}>, given: list<array{type: Equipment, has_variants: bool, remaining: int, stock: int, variants: list<array{variant: EquipmentVariant, remaining: int, stock: int}>}>}
     */
    private function stockLanes(): array
    {
        $warehouse = $this->warehouse();
        $lanes = ['returnable' => [], 'given' => []];

        foreach ($this->catalog() as $type) {
            $kind = $type->returnable ? 'returnable' : 'given';
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

            $lanes[$kind][] = [
                'type' => $type,
                'has_variants' => $type->hasVariants(),
                'remaining' => $remainingTotal,
                'stock' => $stockTotal,
                'variants' => $variants,
            ];
        }

        return $lanes;
    }

    /**
     * @return array{returnable: list<array{index: int, variant: EquipmentVariant, quantity: int}>, given: list<array{index: int, variant: EquipmentVariant, quantity: int}>}
     */
    private function cartLanes(): array
    {
        $variants = $this->catalog()->pluck('variants')->flatten()->keyBy('id');
        $lanes = ['returnable' => [], 'given' => []];

        foreach ($this->lines as $index => $line) {
            $variant = $variants->get((int) $line['variant_id']);
            if (! $variant) {
                continue;
            }
            $kind = $line['kind'] === 'given' ? 'given' : 'returnable';
            $lanes[$kind][] = [
                'index' => $index,
                'variant' => $variant,
                'quantity' => (int) $line['quantity'],
            ];
        }

        return $lanes;
    }

    private function variantFromCatalog(int $variantId): ?EquipmentVariant
    {
        return $this->catalog()->pluck('variants')->flatten()->firstWhere('id', $variantId);
    }

    private function stockFor(int $variantId): int
    {
        return $this->variantFromCatalog($variantId)?->availableIn($this->warehouse()) ?? 0;
    }

    private function persist(EquipmentService $equipmentService): ?int
    {
        $this->flashMessage = null;

        $this->validate([
            'employeeId' => 'required|exists:employees,id',
            'issueDate' => 'required|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:equipment_variants,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.kind' => 'required|in:returnable,given',
        ], [
            'lines.required' => 'Przerzuć co najmniej jedną pozycję na prawą stronę.',
            'lines.min' => 'Przerzuć co najmniej jedną pozycję na prawą stronę.',
        ], [
            'employeeId' => 'pracownik',
            'issueDate' => 'data wydania',
        ]);

        try {
            $this->assertLinesMatchKind();

            $employee = Employee::query()->findOrFail($this->employeeId);

            $issues = $equipmentService->issueItems(
                $employee,
                $this->lines,
                $this->warehouse(),
                Carbon::parse($this->issueDate),
                filled($this->notes) ? $this->notes : null
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return null;
        }

        return $issues->count();
    }

    private function warehouse(): Warehouse
    {
        return Warehouse::query()->with('location')->findOrFail($this->warehouseId);
    }

    private function resetAfterIssue(): void
    {
        $this->employeeId = null;
        $this->employeeSearch = '';
        $this->lines = [];
        $this->expandedTypeId = null;
        $this->resetErrorBag();
    }

    /**
     * @return Collection<int, Employee>
     */
    private function employeeMatches(): Collection
    {
        $query = Employee::query()
            ->whereNull('terminated_at')
            ->orderBy('last_name')
            ->orderBy('first_name');

        $term = trim($this->employeeSearch);
        if ($term === '') {
            return collect();
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';
        $query->where(function ($employees) use ($like) {
            $employees->whereRaw('LOWER(COALESCE(first_name, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(last_name, \'\')) LIKE ?', [$like])
                ->orWhereRaw(
                    'LOWER(TRIM(CONCAT(COALESCE(first_name, \'\'), \' \', COALESCE(last_name, \'\')))) LIKE ?',
                    [$like]
                );
        });

        return $query->limit(12)->get();
    }

    private function assertLinesMatchKind(): void
    {
        foreach ($this->lines as $line) {
            $variant = $this->variantFromCatalog((int) $line['variant_id']);
            $isReturnable = (bool) $variant?->equipment?->returnable;
            $kind = $line['kind'] ?? null;
            if (($kind === 'returnable' && ! $isReturnable) || ($kind === 'given' && $isReturnable) || $variant === null) {
                throw ValidationException::withMessages([
                    'lines' => 'Zwracalne pozycje idą do „Do zwrotu”, bezzwrotne do „Do wydania bezzwrotnie”.',
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
