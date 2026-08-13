<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentVariant;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class WarehouseConsumeForm extends Component
{
    public int $warehouseId;

    public $employeeId = null;

    public string $employeeSearch = '';

    public ?string $notes = null;

    public $addEquipmentId = null;

    public string $equipmentSearch = '';

    public $addVariantId = null;

    public int $addQuantity = 1;

    /** @var array<int, array{variant_id: int, quantity: int}> */
    public array $lines = [];

    private ?Collection $catalogCache = null;

    public function mount(?Warehouse $warehouse = null): void
    {
        $this->warehouseId = ($warehouse && $warehouse->exists)
            ? $warehouse->id
            : app(WarehouseService::class)->current()->id;
    }

    public function updatedAddEquipmentId(): void
    {
        $this->addVariantId = null;
        $variants = $this->variantsForSelectedType();
        if ($variants->count() === 1) {
            $this->addVariantId = $variants->first()->id;
        }
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
            $this->addError('addQuantity', "Niewystarczająca ilość w magazynie. Na stanie: {$remaining}.");

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

    public function save(EquipmentService $equipmentService)
    {
        $this->validate([
            'employeeId' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:equipment_variants,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'lines.required' => 'Dodaj co najmniej jedną pozycję do rozchodu.',
            'lines.min' => 'Dodaj co najmniej jedną pozycję do rozchodu.',
        ]);

        try {
            $employee = $this->employeeId
                ? Employee::query()->findOrFail($this->employeeId)
                : null;

            $movements = $equipmentService->consumeItems(
                $this->lines,
                $this->warehouse(),
                $employee,
                filled($this->notes) ? $this->notes : null
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $count = $movements->count();
        session()->flash('success', "Zaksięgowano rozchód ({$count} {$this->positionsLabel($count)}). Stan został zdjęty.");

        return $this->redirect(route('equipment.tab.issues'), navigate: false);
    }

    public function remainingFor(int $variantId): int
    {
        $variant = $this->catalog()->pluck('variants')->flatten()->firstWhere('id', $variantId);
        $onHand = $variant?->quantityIn($this->warehouse()) ?? 0;
        $inCart = (int) collect($this->lines)
            ->filter(fn ($line) => (int) $line['variant_id'] === $variantId)
            ->sum(fn ($line) => (int) $line['quantity']);

        return max(0, $onHand - $inCart);
    }

    public function render()
    {
        $catalog = $this->catalog();
        $selectedType = $catalog->firstWhere('id', (int) $this->addEquipmentId);

        return view('livewire.warehouse-consume-form', [
            'warehouse' => $this->warehouse(),
            'employeeMatches' => $this->employeeMatches(),
            'equipmentMatches' => $this->equipmentMatches(),
            'catalog' => $catalog,
            'selectedType' => $selectedType,
            'selectedVariants' => $this->variantsForSelectedType(),
            'lineRows' => $this->lineRows(),
        ]);
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
                ->notIssuable()
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

    private function warehouse(): Warehouse
    {
        return Warehouse::query()->with('location')->findOrFail($this->warehouseId);
    }

    private function positionsLabel(int $count): string
    {
        if ($count === 1) {
            return 'pozycja';
        }

        $mod10 = $count % 10;
        $mod100 = $count % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return 'pozycje';
        }

        return 'pozycji';
    }
}
