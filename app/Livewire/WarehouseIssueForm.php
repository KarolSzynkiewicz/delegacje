<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentVariant;
use App\Models\ProjectAssignment;
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

    public $projectAssignmentId = null;

    public string $issueDate = '';

    public ?string $expectedReturnDate = null;

    public ?string $notes = null;

    public $addEquipmentId = null;

    public string $equipmentSearch = '';

    public $addVariantId = null;

    public int $addQuantity = 1;

    /** @var array<int, array{variant_id: int, quantity: int}> */
    public array $lines = [];

    public ?string $flashMessage = null;

    public string $mode = 'returnable';

    private ?Collection $catalogCache = null;

    public function mount(?Warehouse $warehouse = null, string $mode = 'returnable'): void
    {
        $this->warehouseId = ($warehouse && $warehouse->exists)
            ? $warehouse->id
            : app(WarehouseService::class)->current()->id;
        $this->issueDate = now()->toDateString();
        $this->mode = $mode === 'given' ? 'given' : 'returnable';
    }

    public function updatedEmployeeId(): void
    {
        $this->projectAssignmentId = null;
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
        $this->projectAssignmentId = null;
    }

    public function selectEmployee(int $id): void
    {
        $employee = Employee::query()->whereNull('terminated_at')->find($id);
        if (! $employee) {
            return;
        }

        $this->employeeId = $employee->id;
        $this->employeeSearch = $employee->full_name;
        $this->projectAssignmentId = null;
    }

    public function clearEmployee(): void
    {
        $this->employeeId = null;
        $this->employeeSearch = '';
        $this->projectAssignmentId = null;
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

    public function updatedAddEquipmentId(): void
    {
        $this->addVariantId = null;
        $variants = $this->variantsForSelectedType();
        if ($variants->count() === 1) {
            $this->addVariantId = $variants->first()->id;
        }
    }

    public function addLine(): void
    {
        $this->resetErrorBag();
        $this->flashMessage = null;

        $this->validate([
            'addEquipmentId' => 'required|exists:equipment,id',
            'addVariantId' => 'required|exists:equipment_variants,id',
            'addQuantity' => 'required|integer|min:1',
        ], [], [
            'addEquipmentId' => 'typ',
            'addVariantId' => 'rodzaj',
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

        session()->flash('success', $this->isGivenMode()
            ? "Wydano bezzwrotnie {$count} {$this->positionsLabel($count)}."
            : "Wydano {$count} {$this->positionsLabel($count)} do zwrotu.");

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
        $this->flashMessage = $this->isGivenMode()
            ? "Wydano bezzwrotnie {$count} {$this->positionsLabel($count)} dla {$employeeName}. Możesz wydać kolejnej osobie."
            : "Wydano {$count} {$this->positionsLabel($count)} do zwrotu dla {$employeeName}. Możesz wydać kolejnej osobie.";
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
        $catalog = $this->catalog();
        $selectedType = $catalog->firstWhere('id', (int) $this->addEquipmentId);

        return view('livewire.warehouse-issue-form', [
            'warehouse' => $this->warehouse(),
            'employeeMatches' => $this->employeeMatches(),
            'equipmentMatches' => $this->equipmentMatches(),
            'assignments' => $this->assignmentsForEmployee(),
            'catalog' => $catalog,
            'selectedType' => $selectedType,
            'selectedVariants' => $this->variantsForSelectedType(),
            'lineRows' => $this->lineRows(),
            'stockPreview' => $this->stockPreview(),
        ]);
    }

    /**
     * @return Collection<int, Equipment>
     */
    private function catalog(): Collection
    {
        return $this->catalogCache ??= tap(
            Equipment::query()
                ->issuable()
                ->when(
                    $this->isGivenMode(),
                    fn ($query) => $query->where('returnable', false),
                    fn ($query) => $query->where('returnable', true)
                )
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
     * @return Collection<int, ProjectAssignment>
     */
    private function assignmentsForEmployee(): Collection
    {
        if (! $this->employeeId) {
            return collect();
        }

        return ProjectAssignment::active()
            ->where('employee_id', $this->employeeId)
            ->with('project')
            ->get();
    }

    /**
     * @return array<int, array{index: int, variant: EquipmentVariant, quantity: int, remaining_after: int}>
     */
    private function lineRows(): array
    {
        $variants = $this->catalog()->pluck('variants')->flatten()->keyBy('id');
        $warehouse = $this->warehouse();
        $rows = [];

        foreach ($this->lines as $index => $line) {
            $variant = $variants->get((int) $line['variant_id']);
            if (! $variant) {
                continue;
            }
            $quantity = (int) $line['quantity'];
            $stock = $variant->availableIn($warehouse);
            $rows[] = [
                'index' => $index,
                'variant' => $variant,
                'quantity' => $quantity,
                'stock' => $stock,
                'remaining_after' => max(0, $stock - $this->quantityInCart($variant->id)),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array{variant: EquipmentVariant, stock: int, in_cart: int, remaining: int}>
     */
    private function stockPreview(): array
    {
        $variantIds = collect($this->lines)->pluck('variant_id')->map(fn ($id) => (int) $id)->unique();
        if ($variantIds->isEmpty()) {
            return [];
        }

        $variants = $this->catalog()->pluck('variants')->flatten()->keyBy('id');
        $warehouse = $this->warehouse();
        $preview = [];

        foreach ($variantIds as $variantId) {
            $variant = $variants->get($variantId);
            if (! $variant) {
                continue;
            }
            $inCart = $this->quantityInCart($variantId);
            $stock = $variant->availableIn($warehouse);
            $preview[] = [
                'variant' => $variant,
                'stock' => $stock,
                'in_cart' => $inCart,
                'remaining' => max(0, $stock - $inCart),
            ];
        }

        return $preview;
    }

    private function stockFor(int $variantId): int
    {
        $variant = $this->catalog()->pluck('variants')->flatten()->firstWhere('id', $variantId);

        return $variant?->availableIn($this->warehouse()) ?? 0;
    }

    private function persist(EquipmentService $equipmentService): ?int
    {
        $this->flashMessage = null;

        $this->validate([
            'employeeId' => 'required|exists:employees,id',
            'projectAssignmentId' => 'nullable|exists:project_assignments,id',
            'issueDate' => 'required|date',
            'expectedReturnDate' => 'nullable|date|after_or_equal:issueDate',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.variant_id' => 'required|exists:equipment_variants,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'lines.required' => 'Dodaj co najmniej jedną pozycję do wydania.',
            'lines.min' => 'Dodaj co najmniej jedną pozycję do wydania.',
        ], [
            'employeeId' => 'pracownik',
            'issueDate' => 'data wydania',
        ]);

        try {
            $this->assertLinesMatchMode();

            $employee = Employee::query()->findOrFail($this->employeeId);
            $assignment = $this->projectAssignmentId
                ? ProjectAssignment::query()->findOrFail($this->projectAssignmentId)
                : null;

            $issues = $equipmentService->issueItems(
                $employee,
                $this->lines,
                $this->warehouse(),
                Carbon::parse($this->issueDate),
                $this->isGivenMode() || ! $this->expectedReturnDate
                    ? null
                    : Carbon::parse($this->expectedReturnDate),
                $assignment,
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
        $this->projectAssignmentId = null;
        $this->lines = [];
        $this->addEquipmentId = null;
        $this->equipmentSearch = '';
        $this->addVariantId = null;
        $this->addQuantity = 1;
        $this->catalogCache = null;
        $this->resetErrorBag();
    }

    public function isGivenMode(): bool
    {
        return $this->mode === 'given';
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
            ->filter(fn (Equipment $type) => str_contains(mb_strtolower($type->name), $needle)
                || str_contains(mb_strtolower((string) $type->category), $needle))
            ->take(12)
            ->values();
    }

    private function assertLinesMatchMode(): void
    {
        $catalogVariants = $this->catalog()->pluck('variants')->flatten()->keyBy('id');

        foreach ($this->lines as $line) {
            if (! $catalogVariants->has((int) $line['variant_id'])) {
                throw ValidationException::withMessages([
                    'lines' => $this->isGivenMode()
                        ? 'Można wydać bezzwrotnie tylko pozycje niezwracalne.'
                        : 'Można wydać do zwrotu tylko pozycje zwracalne.',
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
