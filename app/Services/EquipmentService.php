<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Models\ProjectAssignment;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service for managing equipment catalog, warehouse stock, issues and returns.
 */
class EquipmentService
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{id?: int|null, value?: string|null, quantity_in_stock: int, min_quantity: int}>  $variants
     */
    public function saveType(array $attributes, array $variants, Warehouse $warehouse, ?Equipment $equipment = null): Equipment
    {
        $this->assertUniqueVariantValues($variants);

        $attributes['issuable'] = (bool) ($attributes['issuable'] ?? true);
        $attributes['returnable'] = $attributes['issuable'] && (bool) ($attributes['returnable'] ?? false);

        if (! ($attributes['issuable'] ?? true)) {
            $attributes['returnable'] = false;
        }

        return DB::transaction(function () use ($attributes, $variants, $warehouse, $equipment) {
            $equipment = $equipment ?? new Equipment;
            $equipment->fill($attributes);
            $equipment->save();

            $keptIds = [];

            foreach (array_values($variants) as $index => $row) {
                $payload = [
                    'value' => filled($row['value'] ?? null) ? trim((string) $row['value']) : null,
                    'sort_order' => $index,
                ];

                $variantId = $row['id'] ?? null;
                if ($variantId) {
                    $variant = $equipment->variants()->whereKey($variantId)->first();
                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'variants' => 'Jeden z rodzajów nie należy do tego typu.',
                        ]);
                    }
                    $variant->update($payload);
                    $keptIds[] = $variant->id;
                } else {
                    $variant = $equipment->variants()->create($payload);
                    $keptIds[] = $variant->id;
                }

                EquipmentStock::query()->updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'equipment_variant_id' => $variant->id,
                    ],
                    [
                        'quantity_in_stock' => (int) ($row['quantity_in_stock'] ?? 0),
                        'min_quantity' => (int) ($row['min_quantity'] ?? 0),
                    ]
                );
            }

            $toDelete = $equipment->variants()->whereNotIn('id', $keptIds)->get();
            foreach ($toDelete as $variant) {
                if ($variant->issues()->exists()) {
                    throw ValidationException::withMessages([
                        'variants' => "Nie można usunąć rodzaju „{$variant->kind_label}” — istnieją wydania.",
                    ]);
                }
                $variant->delete();
            }

            return $equipment->fresh(['variants.stocks']);
        });
    }

    /**
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     * @return Collection<int, EquipmentIssue>
     */
    public function issueItems(
        Employee $employee,
        array $lines,
        Warehouse $warehouse,
        Carbon $issueDate,
        ?Carbon $expectedReturnDate = null,
        ?ProjectAssignment $projectAssignment = null,
        ?string $notes = null
    ): Collection {
        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Dodaj co najmniej jedną pozycję do wydania.',
            ]);
        }

        return DB::transaction(function () use ($employee, $lines, $warehouse, $issueDate, $expectedReturnDate, $projectAssignment, $notes) {
            $variantIds = collect($lines)->pluck('variant_id')->unique()->sort()->values();
            $variants = EquipmentVariant::query()
                ->with('equipment')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $stocks = EquipmentStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('equipment_variant_id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('equipment_variant_id');

            $batchId = (string) Str::uuid();
            $issues = collect();

            foreach ($lines as $index => $line) {
                $variant = $variants->get($line['variant_id']);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.variant_id" => 'Wybrany rodzaj nie istnieje.',
                    ]);
                }

                if (! $variant->equipment?->issuable) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.variant_id" => "„{$variant->display_name}” nie jest wydawany pracownikom.",
                    ]);
                }

                $available = $variant->availableIn($warehouse);
                if ($available < $line['quantity']) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => "Niewystarczająca ilość w magazynie „{$warehouse->name}” dla „{$variant->display_name}”. Dostępne: {$available}, żądane: {$line['quantity']}.",
                    ]);
                }

                $returnable = (bool) $variant->equipment?->returnable;
                if (! $returnable) {
                    $stock = $stocks->get($variant->id);
                    if (! $stock) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.quantity" => "Brak stanu w magazynie „{$warehouse->name}” dla „{$variant->display_name}”.",
                        ]);
                    }
                    $stock->decrement('quantity_in_stock', $line['quantity']);
                    $variant->unsetRelation('stocks');
                }

                $issues->push(EquipmentIssue::create([
                    'equipment_id' => $variant->equipment_id,
                    'equipment_variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'project_assignment_id' => $projectAssignment?->id,
                    'quantity_issued' => $line['quantity'],
                    'issue_date' => $issueDate,
                    'expected_return_date' => $returnable ? $expectedReturnDate : null,
                    'status' => $returnable ? EquipmentIssue::STATUS_ISSUED : EquipmentIssue::STATUS_GIVEN,
                    'notes' => $notes,
                    'batch_id' => $batchId,
                    'issued_by' => auth()->id(),
                ]));
            }

            return $issues;
        });
    }

    /**
     * Rozchód: zdejmuje stan od razu. Nie tworzy wydania na pracownika.
     *
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     * @return Collection<int, EquipmentStockMovement>
     */
    public function consumeItems(
        array $lines,
        Warehouse $warehouse,
        ?Employee $employee = null,
        ?string $notes = null
    ): Collection {
        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Dodaj co najmniej jedną pozycję do rozchodu.',
            ]);
        }

        return DB::transaction(function () use ($lines, $warehouse, $employee, $notes) {
            $variantIds = collect($lines)->pluck('variant_id')->unique()->sort()->values();
            $variants = EquipmentVariant::query()
                ->with('equipment')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $stocks = EquipmentStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('equipment_variant_id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('equipment_variant_id');

            $batchId = (string) Str::uuid();
            $movements = collect();

            foreach ($lines as $index => $line) {
                $variant = $variants->get($line['variant_id']);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.variant_id" => 'Wybrany rodzaj nie istnieje.',
                    ]);
                }

                if ($variant->equipment?->issuable) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.variant_id" => "„{$variant->display_name}” jest wydawany pracownikom — użyj wydania z magazynu, nie rozchodu.",
                    ]);
                }

                $stock = $stocks->get($variant->id);
                $onHand = (int) ($stock?->quantity_in_stock ?? 0);
                if ($onHand < $line['quantity']) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.quantity" => "Niewystarczająca ilość w magazynie „{$warehouse->name}” dla „{$variant->display_name}”. Na stanie: {$onHand}, żądane: {$line['quantity']}.",
                    ]);
                }

                $stock->decrement('quantity_in_stock', $line['quantity']);

                $movements->push(EquipmentStockMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'equipment_id' => $variant->equipment_id,
                    'equipment_variant_id' => $variant->id,
                    'type' => StockMovementType::CONSUMPTION,
                    'quantity' => $line['quantity'],
                    'employee_id' => $employee?->id,
                    'notes' => $notes,
                    'batch_id' => $batchId,
                    'created_by' => auth()->id(),
                ]));
            }

            return $movements;
        });
    }

    public function issueEquipment(
        EquipmentVariant $variant,
        Employee $employee,
        Warehouse $warehouse,
        int $quantityIssued,
        Carbon $issueDate,
        ?Carbon $expectedReturnDate = null,
        ?ProjectAssignment $projectAssignment = null,
        ?string $notes = null
    ): EquipmentIssue {
        return $this->issueItems(
            $employee,
            [['variant_id' => $variant->id, 'quantity' => $quantityIssued]],
            $warehouse,
            $issueDate,
            $expectedReturnDate,
            $projectAssignment,
            $notes
        )->first();
    }

    public function returnEquipment(
        EquipmentIssue $equipmentIssue,
        Carbon $returnDate,
        string $status = 'returned',
        ?string $notes = null
    ): bool {
        if (! in_array($status, ['returned', 'damaged', 'lost'], true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}. Must be 'returned', 'damaged', or 'lost'.");
        }

        if (! $equipmentIssue->equipment->issuable || ! $equipmentIssue->equipment->returnable) {
            throw ValidationException::withMessages([
                'equipment' => 'Ta pozycja nie może być zwracana, zgłaszana jako uszkodzona lub zgubiona.',
            ]);
        }

        $equipmentIssue->markAsReturned($returnDate, auth()->id(), $status);

        if ($notes !== null) {
            $equipmentIssue->update(['notes' => $notes]);
        }

        return true;
    }

    public function getRequiredEquipmentForRole(int $roleId): Collection
    {
        return Equipment::whereHas('requirements', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    /**
     * @return array{has_all: bool, missing: array<int, array{equipment: Equipment, required: int, issued: int}>}
     */
    public function checkEmployeeEquipmentForRole(Employee $employee, int $roleId): array
    {
        $requiredEquipment = $this->getRequiredEquipmentForRole($roleId);
        $missing = [];

        foreach ($requiredEquipment as $equipment) {
            $requirement = $equipment->requirements()->where('role_id', $roleId)->first();
            $requiredQuantity = $requirement ? $requirement->required_quantity : 1;

            $issuedQuantity = EquipmentIssue::where('equipment_id', $equipment->id)
                ->where('employee_id', $employee->id)
                ->where('status', 'issued')
                ->sum('quantity_issued');

            if ($issuedQuantity < $requiredQuantity) {
                $missing[] = [
                    'equipment' => $equipment,
                    'required' => $requiredQuantity,
                    'issued' => $issuedQuantity,
                ];
            }
        }

        return [
            'has_all' => empty($missing),
            'missing' => $missing,
        ];
    }

    /**
     * @param  array<int, array{id?: int|null, value?: string|null, quantity_in_stock?: mixed, min_quantity?: mixed}>  $variants
     */
    private function assertUniqueVariantValues(array $variants): void
    {
        $normalized = collect($variants)
            ->map(fn ($row) => filled($row['value'] ?? null) ? mb_strtolower(trim((string) $row['value'])) : '');

        if ($normalized->count() !== $normalized->unique()->count()) {
            throw ValidationException::withMessages([
                'variants' => 'Każdy rodzaj w ramach typu musi mieć unikalną nazwę.',
            ]);
        }
    }

    /**
     * @param  array<int, array{variant_id?: mixed, quantity?: mixed}>  $lines
     * @return array<int, array{variant_id: int, quantity: int}>
     */
    private function normalizeIssueLines(array $lines): array
    {
        $merged = [];

        foreach ($lines as $line) {
            $variantId = (int) ($line['variant_id'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);
            if ($variantId < 1 || $quantity < 1) {
                continue;
            }
            $merged[$variantId] = ($merged[$variantId] ?? 0) + $quantity;
        }

        return collect($merged)
            ->map(fn (int $quantity, int $variantId) => [
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ])
            ->values()
            ->all();
    }
}
