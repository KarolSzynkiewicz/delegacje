<?php

namespace App\Livewire;

use App\Enums\Currency;
use App\Models\Equipment;
use App\Models\Warehouse;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class EquipmentForm extends Component
{
    public ?int $equipmentId = null;

    public int $warehouseId;

    public string $name = '';

    public string $description = '';

    public string $category = '';

    public bool $has_variants = false;

    public string $variant_label = '';

    public $unit_cost = null;

    public string $currency = 'PLN';

    public bool $issuable = true;

    public bool $returnable = true;

    /** @var array<int, array{id: int|null, value: string, quantity_in_stock: int|string, min_quantity: int|string}> */
    public array $variants = [];

    public function mount(?Equipment $equipment = null, ?Warehouse $warehouse = null): void
    {
        $this->warehouseId = ($warehouse && $warehouse->exists)
            ? $warehouse->id
            : app(WarehouseService::class)->current()->id;

        if ($equipment && $equipment->exists) {
            $warehouse = Warehouse::query()->findOrFail($this->warehouseId);
            $equipment->loadMissing(['variants.stocks' => fn ($stocks) => $stocks->where('warehouse_id', $warehouse->id)]);

            $this->equipmentId = $equipment->id;
            $this->name = $equipment->name;
            $this->description = $equipment->description ?? '';
            $this->category = $equipment->category ?? '';
            $this->has_variants = $equipment->hasVariants();
            $this->variant_label = $equipment->variant_label ?? '';
            $this->unit_cost = $equipment->unit_cost;
            $this->currency = $equipment->currency?->value ?? 'PLN';
            $this->issuable = (bool) $equipment->issuable;
            $this->returnable = (bool) $equipment->returnable;
            $this->variants = $equipment->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'value' => $variant->value ?? '',
                'quantity_in_stock' => $variant->quantityIn($warehouse),
                'min_quantity' => $variant->minQuantityIn($warehouse),
            ])->values()->all();
        }

        if ($this->variants === []) {
            $this->addVariant();
        }
    }

    public function updatedIssuable(bool $value): void
    {
        if (! $value) {
            $this->returnable = false;
        }
    }

    public function updatedHasVariants(bool $value): void
    {
        if ($value) {
            if ($this->variants === []) {
                $this->addVariant();
            }

            return;
        }

        $this->variant_label = '';
        $first = $this->variants[0] ?? $this->blankVariant();
        $first['value'] = '';
        $this->variants = [$first];
    }

    public function addVariant(): void
    {
        $this->variants[] = $this->blankVariant();
    }

    public function removeVariant(int $index): void
    {
        if (! $this->has_variants || count($this->variants) <= 1) {
            $this->addError('variants', 'Zostaw co najmniej jeden wariant albo wyłącz warianty.');

            return;
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(EquipmentService $equipmentService)
    {
        $this->validate($this->rules());

        try {
            $equipment = $this->equipmentId
                ? Equipment::query()->findOrFail($this->equipmentId)
                : null;
            $warehouse = Warehouse::query()->findOrFail($this->warehouseId);

            $saved = $equipmentService->saveType(
                [
                    'name' => $this->name,
                    'description' => $this->description !== '' ? $this->description : null,
                    'category' => $this->category !== '' ? $this->category : null,
                    'variant_label' => $this->has_variants && $this->variant_label !== '' ? $this->variant_label : null,
                    'unit_cost' => $this->unit_cost !== '' && $this->unit_cost !== null ? $this->unit_cost : null,
                    'currency' => $this->currency,
                    'issuable' => $this->issuable,
                    'returnable' => $this->issuable && $this->returnable,
                ],
                $this->variantsForSave(),
                $warehouse,
                $equipment
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        session()->flash(
            'success',
            $this->equipmentId ? 'Pozycja magazynowa została zaktualizowana.' : 'Pozycja magazynowa została dodana.'
        );

        return $this->redirect(route('equipment.show', [
            'equipment' => $saved,
            'warehouse_id' => $this->warehouseId,
        ]), navigate: false);
    }

    public function render()
    {
        return view('livewire.equipment-form', [
            'warehouse' => Warehouse::query()->with('location')->find($this->warehouseId),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'has_variants' => 'boolean',
            'variant_label' => $this->has_variants ? 'required|string|max:255' : 'nullable|string|max:255',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => ['required', 'string', Rule::in(Currency::values())],
            'issuable' => 'boolean',
            'returnable' => 'boolean',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.value' => $this->has_variants ? 'required|string|max:255' : 'nullable|string|max:255',
            'variants.*.quantity_in_stock' => 'required|integer|min:0',
            'variants.*.min_quantity' => 'required|integer|min:0',
        ];
    }

    /**
     * @return array{id: null, value: string, quantity_in_stock: int, min_quantity: int}
     */
    private function blankVariant(): array
    {
        return [
            'id' => null,
            'value' => '',
            'quantity_in_stock' => 0,
            'min_quantity' => 0,
        ];
    }

    /**
     * @return array<int, array{id: int|null, value: string|null, quantity_in_stock: int|string, min_quantity: int|string}>
     */
    private function variantsForSave(): array
    {
        if ($this->has_variants) {
            return $this->variants;
        }

        $first = $this->variants[0] ?? $this->blankVariant();

        return [[
            'id' => $first['id'] ?? null,
            'value' => null,
            'quantity_in_stock' => $first['quantity_in_stock'] ?? 0,
            'min_quantity' => $first['min_quantity'] ?? 0,
        ]];
    }
}
