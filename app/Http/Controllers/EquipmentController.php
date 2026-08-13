<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Enums\StockMovementType;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStockMovement;
use App\Models\Role;
use App\Services\EquipmentService;
use App\Services\WarehouseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EquipmentController extends Controller
{
    public function __construct(protected WarehouseService $warehouseService) {}

    public function index(Request $request)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        $query = Equipment::query()->withWarehouseInventory($warehouse);

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
            $query->where('category', 'like', '%'.addcslashes(trim($request->string('category')->toString()), '%_\\').'%');
        }

        $equipment = $query->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('equipment.index', compact('equipment', 'warehouse', 'warehouses') + [
            'activeTab' => 'stock',
        ]);
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
                fn (string $status) => $status !== EquipmentIssue::STATUS_GIVEN
            ));
        }
        $warehouse = $this->warehouseService->current($request);

        return view('equipment.issues', compact(
            'entries',
            'statuses',
            'warehouse',
            'kind',
        ) + [
            'activeTab' => 'issues',
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
            ->with('success', 'Pozycja magazynowa została dodana.');
    }

    public function show(Request $request, Equipment $equipment)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();

        $equipment = Equipment::query()
            ->whereKey($equipment->id)
            ->withWarehouseInventory($warehouse)
            ->with([
                'requirements.role',
                'issues' => fn ($issues) => $issues->where('warehouse_id', $warehouse->id)->latest('issue_date')->limit(10),
                'issues.employee',
                'issues.variant',
                'issues.projectAssignment',
            ])
            ->firstOrFail();

        return view('equipment.show', compact('equipment', 'warehouse', 'warehouses'));
    }

    public function edit(Request $request, Equipment $equipment)
    {
        $warehouse = $this->warehouseService->current($request);
        $warehouses = $this->warehouseService->all();
        $equipment->load(['variants.stocks' => fn ($stocks) => $stocks->where('warehouse_id', $warehouse->id)]);
        $roles = Role::orderBy('name')->get();

        return view('equipment.edit', compact('equipment', 'roles', 'warehouse', 'warehouses'));
    }

    public function update(Request $request, Equipment $equipment, EquipmentService $equipmentService)
    {
        $warehouse = $this->warehouseService->current($request);
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

    public function destroy(Equipment $equipment)
    {
        if ($equipment->issues()->exists()) {
            return redirect()
                ->route('equipment.index')
                ->with('error', 'Nie można usunąć pozycji, która ma wydania.');
        }

        $equipment->delete();

        return redirect()
            ->route('equipment.index')
            ->with('success', 'Pozycja magazynowa została usunięta.');
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
            'variants.*.quantity_in_stock' => 'required|integer|min:0',
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
                'quantity_in_stock' => $validated['variants'][0]['quantity_in_stock'],
                'min_quantity' => $validated['variants'][0]['min_quantity'],
            ]],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function outboundHistory(Request $request, string $kind): LengthAwarePaginator
    {
        $rows = collect();

        if ($kind !== 'consumption') {
            $issues = EquipmentIssue::query()
                ->with(['equipment', 'variant', 'employee', 'issuer', 'warehouse.location'])
                ->when($kind === 'issue', fn ($query) => $query->where('status', '!=', EquipmentIssue::STATUS_GIVEN))
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
                ->with(['equipment', 'variant', 'employee', 'creator', 'warehouse.location'])
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
