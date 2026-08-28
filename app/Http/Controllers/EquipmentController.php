<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Enums\StockMovementType;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\WarehouseDispatch;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService) {}

    public function index(Request $request)
    {
        return $this->stockIndex($request);
    }

    public function indexArchived(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);

        return redirect()->route('equipment.tab.stock', array_filter([
            'warehouse_id' => $warehouse->id,
            'withdrawn' => 1,
            'search' => $request->query('search'),
            'category' => $request->query('category'),
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function indexIssues(Request $request)
    {
        $kind = $request->query('kind', 'all');
        if (! in_array($kind, ['all', 'issue', 'given', 'consumption'], true)) {
            $kind = 'all';
        }

        $entries = $this->outboundHistory($request, $kind);
        $statuses = EquipmentIssue::filterStatuses();
        if ($kind === 'given') {
            $statuses = [EquipmentIssue::STATUS_GIVEN];
        } elseif ($kind === 'issue') {
            $statuses = array_values(array_filter(
                $statuses,
                fn (string $status) => ! in_array($status, [EquipmentIssue::STATUS_GIVEN, EquipmentIssue::STATUS_UNFULFILLED], true)
            ));
        }
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        return view('equipment.issues', compact(
            'entries',
            'statuses',
            'warehouse',
            'warehouses',
            'kind',
        ) + [
            'warehouseCounts' => $this->warehouseService->assortmentCounts($warehouses),
            'activeTab' => 'issues',
        ]);
    }

    public function indexOrders(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        $pendingDispatches = WarehouseDispatch::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('status', WarehouseDispatch::STATUS_RESERVED)
            ->with(['creator', 'issues.employee'])
            ->latest('id')
            ->get();

        return view('equipment.orders', compact(
            'pendingDispatches',
            'warehouse',
            'warehouses',
        ) + [
            'warehouseCounts' => $this->warehouseService->assortmentCounts($warehouses),
            'activeTab' => 'orders',
        ]);
    }

    public function indexWarehouses()
    {
        return view('equipment.warehouses', [
            'activeTab' => 'warehouses',
        ]);
    }

    public function create(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        return view('equipment.create', compact('warehouse', 'warehouses'));
    }

    public function store(Request $request, EquipmentService $equipmentService)
    {
        $warehouse = $this->warehouseService->current($request);
        $validated = $this->validatedType($request);

        try {
            $equipment = $equipmentService->saveType($validated['type'], $validated['variants'], $warehouse);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id])
            ->with('success', 'Pozycja dodana do katalogu. Przyjmij towar, żeby pojawił się stan.');
    }

    public function show(Request $request, Equipment $equipment)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        $equipment = Equipment::query()
            ->whereKey($equipment->id)
            ->with([
                'requirements.role',
                'variants' => function ($variants) {
                    $variants
                        ->with(['stocks.warehouse.location'])
                        ->withSum([
                            'issues as issued_outstanding_total' => function ($issues) {
                                $issues->where('status', EquipmentIssue::STATUS_ISSUED);
                            },
                        ], 'quantity_issued');
                },
            ])
            ->firstOrFail();

        $distributions = $this->variantDistributions($equipment, $warehouses);
        $equipment->variants->each->setRelation('equipment', $equipment);

        return view('equipment.show', [
            'equipment' => $equipment,
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'distributions' => $distributions,
            'variantOverview' => $this->variantOverview($distributions),
            'locationLegend' => $this->locationLegend($distributions, $warehouses),
            'barScale' => max(1, (int) $distributions->max('total'), (int) $distributions->max('min')),
        ]);
    }

    public function edit(Request $request, Equipment $equipment)
    {
        $warehouse = $this->warehouseService->current($request);

        if ($equipment->isArchived()) {
            return redirect()
                ->route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id])
                ->with('error', 'Przywróć pozycję do asortymentu, żeby ją edytować.');
        }

        $warehouses = $this->warehouseService->all();
        $equipment->load(['variants.stocks' => fn ($stocks) => $stocks->where('warehouse_id', $warehouse->id)]);
        $roles = Role::orderBy('name')->get();

        return view('equipment.edit', compact('equipment', 'roles', 'warehouse', 'warehouses'));
    }

    public function update(Request $request, Equipment $equipment, EquipmentService $equipmentService)
    {
        $warehouse = $this->warehouseService->current($request);

        if ($equipment->isArchived()) {
            return redirect()
                ->route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id])
                ->with('error', 'Przywróć pozycję do asortymentu, żeby ją edytować.');
        }
        $validated = $this->validatedType($request);

        try {
            $equipmentService->saveType($validated['type'], $validated['variants'], $warehouse, $equipment);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('equipment.show', ['equipment' => $equipment, 'warehouse_id' => $warehouse->id])
            ->with('success', 'Pozycja magazynowa została zaktualizowana.');
    }

    public function destroy(Request $request, Equipment $equipment, EquipmentService $equipmentService)
    {
        $warehouse = $this->warehouseService->current($request);

        try {
            $equipmentService->archiveType($equipment);
        } catch (ValidationException $e) {
            return redirect()
                ->route('equipment.tab.stock', ['warehouse_id' => $warehouse->id])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('equipment.tab.stock', ['warehouse_id' => $warehouse->id, 'withdrawn' => 1])
            ->with('success', 'Pozycja została wycofana z ewidencji. Historia wydań została zachowana.');
    }

    public function restore(Request $request, Equipment $equipment, EquipmentService $equipmentService)
    {
        $warehouse = $this->warehouseService->current($request);

        try {
            $equipmentService->restoreType($equipment);
        } catch (ValidationException $e) {
            return redirect()
                ->route('equipment.tab.stock', ['warehouse_id' => $warehouse->id, 'withdrawn' => 1])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('equipment.tab.stock', ['warehouse_id' => $warehouse->id])
            ->with('success', 'Pozycja została przywrócona do asortymentu.');
    }

    /**
     * @return array{type: array<string, mixed>, variants: array<int, array<string, mixed>>}
     */
    private function validatedType(Request $request): array
    {
        $hasVariants = $request->boolean('has_variants');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'variant_label' => $hasVariants ? 'required|string|max:255' : 'nullable|string|max:255',
            'unit_cost' => 'nullable|numeric|min:0',
            'currency' => ['required', 'string', Rule::in(Currency::values())],
            'issuable' => 'nullable|boolean',
            'returnable' => 'nullable|boolean',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.value' => $hasVariants ? 'required|string|max:255' : 'nullable|string|max:255',
            'variants.*.min_quantity' => 'required|integer|min:0',
        ]);

        $issuable = $request->boolean('issuable', true);

        return [
            'type' => [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'category' => $validated['category'] ?? null,
                'variant_label' => $hasVariants ? ($validated['variant_label'] ?? null) : null,
                'unit_cost' => $validated['unit_cost'] ?? null,
                'currency' => $validated['currency'] ?? 'PLN',
                'issuable' => $issuable,
                'returnable' => $issuable && $request->boolean('returnable'),
            ],
            'variants' => $hasVariants ? $validated['variants'] : [[
                'id' => $validated['variants'][0]['id'] ?? null,
                'value' => null,
                'min_quantity' => $validated['variants'][0]['min_quantity'],
            ]],
        ];
    }

    private function stockIndex(Request $request): \Illuminate\View\View
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();
        $showWithdrawn = $request->boolean('withdrawn');

        $query = Equipment::query()
            ->when(! $showWithdrawn, fn ($items) => $items->active())
            ->withWarehouseInventory($warehouse);

        if ($request->filled('search')) {
            $searchTerm = trim($request->search);
            if (strlen($searchTerm) >= 2) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('description', 'like', '%'.$searchTerm.'%')
                        ->orWhereHas('variants', function ($variants) use ($searchTerm) {
                            $variants->where('value', 'like', '%'.$searchTerm.'%');
                        });
                });
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        $equipment = $query->orderBy('name')->get();

        $categories = Equipment::query()
            ->when(! $showWithdrawn, fn ($items) => $items->active())
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('equipment.index', [
            'sections' => $this->stockSections($equipment),
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'warehouseCounts' => $this->warehouseService->assortmentCounts($warehouses),
            'categories' => $categories,
            'activeTab' => 'stock',
            'showWithdrawn' => $showWithdrawn,
        ]);
    }

    /**
     * @param  Collection<int, Equipment>  $equipment
     * @return Collection<int, array{title: string, groups: list<array{title: string|null, items: Collection<int, Equipment>}>}>
     */
    private function stockSections(Collection $equipment): Collection
    {
        $active = $equipment->filter(fn (Equipment $item) => ! $item->isArchived())->values();
        $withdrawn = $equipment->filter(fn (Equipment $item) => $item->isArchived())->values();

        $sections = [
            [
                'title' => 'Asortyment dla pracowników',
                'groups' => [
                    [
                        'title' => 'Zwracalny',
                        'items' => $active->filter(fn (Equipment $item) => $item->issuable && $item->returnable)->values(),
                    ],
                    [
                        'title' => 'Niezwracalny',
                        'items' => $active->filter(fn (Equipment $item) => $item->issuable && ! $item->returnable)->values(),
                    ],
                ],
            ],
            [
                'title' => 'Inny asortyment',
                'groups' => [
                    [
                        'title' => null,
                        'items' => $active->filter(fn (Equipment $item) => ! $item->issuable)->values(),
                    ],
                ],
            ],
        ];

        if ($withdrawn->isNotEmpty()) {
            $sections[] = [
                'title' => 'Wycofane',
                'groups' => [
                    [
                        'title' => null,
                        'items' => $withdrawn,
                    ],
                ],
            ];
        }

        return collect($sections)
            ->map(function (array $section) {
                $section['groups'] = collect($section['groups'])
                    ->filter(fn (array $group) => $group['items']->isNotEmpty())
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section) => $section['groups'] !== [])
            ->values();
    }

    /**
     * @param  Collection<int, Warehouse>  $warehouses
     * @return Collection<int, array{variant: EquipmentVariant, label: string, color: string, slices: list<array{label: string, value: int, color: string, warehouse_id: int|null}>, on_shelf: int, with_people: int, total: int, min: int, coverage: int, low: bool}>
     */
    private function variantDistributions(Equipment $equipment, Collection $warehouses): Collection
    {
        $variantPalette = ['#22d3ee', '#a78bfa', '#2dd4bf', '#f59e0b', '#38bdf8', '#f472b6', '#84cc16', '#818cf8'];
        $locationPalette = ['#f97316', '#38bdf8', '#a78bfa', '#34d399', '#fbbf24', '#fb7185', '#818cf8', '#14b8a6'];
        $peopleColor = '#94a3b8';
        $warehouseColors = $warehouses->values()->mapWithKeys(
            fn (Warehouse $warehouse, int $index) => [$warehouse->id => $locationPalette[$index % count($locationPalette)]]
        );

        return $equipment->variants->values()->map(function (EquipmentVariant $variant, int $index) use ($equipment, $warehouses, $variantPalette, $warehouseColors, $peopleColor) {
            $slices = [];
            $onShelf = 0;

            foreach ($warehouses as $warehouse) {
                $qty = $variant->quantityIn($warehouse);
                $onShelf += $qty;
                if ($qty < 1) {
                    continue;
                }

                $slices[] = [
                    'label' => $warehouse->display_name,
                    'value' => $qty,
                    'color' => $warehouseColors[$warehouse->id],
                    'warehouse_id' => $warehouse->id,
                ];
            }

            $withPeople = ($equipment->issuable && $equipment->returnable)
                ? $variant->issuedOutstandingTotal()
                : 0;

            if ($withPeople > 0) {
                $slices[] = [
                    'label' => 'U ludzi',
                    'value' => $withPeople,
                    'color' => $peopleColor,
                    'warehouse_id' => null,
                ];
            }

            $min = $variant->minQuantityTotal();
            $total = $onShelf + $withPeople;
            $coverage = $min > 0 ? min(100, (int) round(($total / $min) * 100)) : ($total > 0 ? 100 : 0);

            return [
                'variant' => $variant,
                'label' => $equipment->hasVariants() ? $variant->kind_label : $equipment->name,
                'color' => $variantPalette[$index % count($variantPalette)],
                'slices' => $slices,
                'on_shelf' => $onShelf,
                'with_people' => $withPeople,
                'total' => $total,
                'min' => $min,
                'coverage' => $coverage,
                'low' => $min > 0 && $total < $min,
            ];
        });
    }

    /**
     * @param  Collection<int, array{label: string, color: string, total: int}>  $distributions
     * @return array{slices: list<array{label: string, value: int, color: string}>, total: int}
     */
    private function variantOverview(Collection $distributions): array
    {
        $slices = $distributions
            ->filter(fn (array $distribution) => (int) $distribution['total'] > 0)
            ->map(fn (array $distribution) => [
                'label' => $distribution['label'],
                'value' => (int) $distribution['total'],
                'color' => $distribution['color'],
            ])
            ->values()
            ->all();

        return [
            'slices' => $slices,
            'total' => (int) $distributions->sum('total'),
        ];
    }

    /**
     * @param  Collection<int, array{slices: list<array{label: string, value: int, color: string, warehouse_id: int|null}>}>  $distributions
     * @param  Collection<int, Warehouse>  $warehouses
     * @return list<array{label: string, color: string}>
     */
    private function locationLegend(Collection $distributions, Collection $warehouses): array
    {
        $used = [];

        foreach ($distributions as $distribution) {
            foreach ($distribution['slices'] as $slice) {
                if ((int) $slice['value'] < 1) {
                    continue;
                }

                $key = $slice['warehouse_id'] ?? 'people';
                $used[$key] = [
                    'label' => $slice['label'],
                    'color' => $slice['color'],
                ];
            }
        }

        $legend = [];
        foreach ($warehouses as $warehouse) {
            if (isset($used[$warehouse->id])) {
                $legend[] = $used[$warehouse->id];
            }
        }

        if (isset($used['people'])) {
            $legend[] = $used['people'];
        }

        return $legend;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function outboundHistory(Request $request, string $kind): LengthAwarePaginator
    {
        $rows = collect();

        if ($kind !== 'consumption') {
            $issues = EquipmentIssue::query()
                ->with(['equipment', 'variant', 'employee', 'issuer', 'warehouse.location', 'dispatch'])
                ->where('status', '!=', EquipmentIssue::STATUS_RESERVED)
                ->when($kind === 'issue', fn ($query) => $query->whereNotIn('status', [EquipmentIssue::STATUS_GIVEN, EquipmentIssue::STATUS_UNFULFILLED]))
                ->when($kind === 'given', fn ($query) => $query->where('status', EquipmentIssue::STATUS_GIVEN))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
                ->when($request->filled('employee'), function ($query) use ($request) {
                    $term = '%'.addcslashes(trim($request->string('employee')->toString()), '%_\\').'%';
                    $query->whereHas('employee', function ($employees) use ($term) {
                        $employees->where(function ($name) use ($term) {
                            $name->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term);
                        });
                    });
                })
                ->when($request->filled('item'), function ($query) use ($request) {
                    $term = '%'.addcslashes(trim($request->string('item')->toString()), '%_\\').'%';
                    $query->whereHas('equipment', fn ($equipment) => $equipment->where('name', 'like', $term));
                })
                ->get();

            $rows = $rows->concat($issues->map(function (EquipmentIssue $issue) {
                $happenedAt = $issue->issue_date?->startOfDay() ?? $issue->created_at;

                return [
                    'key' => 'issue-'.$issue->id,
                    'kind' => 'issue',
                    'happened_at' => $happenedAt,
                    'sort_at' => $issue->created_at ?? $happenedAt,
                    'issue' => $issue,
                    'movement' => null,
                ];
            }));
        }

        if ($kind === 'consumption' || ($kind === 'all' && ! $request->filled('status'))) {
            $movements = EquipmentStockMovement::query()
                ->with(['equipment', 'variant', 'employee', 'consumedFor', 'creator', 'warehouse.location'])
                ->where('type', StockMovementType::CONSUMPTION)
                ->when($request->filled('employee'), function ($query) use ($request) {
                    $term = '%'.addcslashes(trim($request->string('employee')->toString()), '%_\\').'%';
                    $query->whereHas('employee', function ($employees) use ($term) {
                        $employees->where(function ($name) use ($term) {
                            $name->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term);
                        });
                    });
                })
                ->when($request->filled('item'), function ($query) use ($request) {
                    $term = '%'.addcslashes(trim($request->string('item')->toString()), '%_\\').'%';
                    $query->whereHas('equipment', fn ($equipment) => $equipment->where('name', 'like', $term));
                })
                ->get();

            $rows = $rows->concat($movements->map(function (EquipmentStockMovement $movement) {
                return [
                    'key' => 'consumption-'.$movement->id,
                    'kind' => 'consumption',
                    'happened_at' => $movement->created_at,
                    'sort_at' => $movement->created_at,
                    'issue' => null,
                    'movement' => $movement,
                ];
            }));
        }

        $sorted = $rows
            ->sortByDesc(function (array $row) {
                $sortAt = $row['sort_at'];
                $timestamp = $sortAt instanceof Carbon ? $sortAt->timestamp : 0;

                return sprintf('%011d-%s', $timestamp, $row['key']);
            })
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;

        return new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
