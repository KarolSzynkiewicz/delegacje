<?php

namespace App\Services;

use App\Enums\ConsumptionDestination;
use App\Enums\LogisticsEventStatus;
use App\Enums\LogisticsEventType;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\EquipmentIssue;
use App\Models\EquipmentStock;
use App\Models\EquipmentStockMovement;
use App\Models\EquipmentVariant;
use App\Models\LogisticsEvent;
use App\Models\Warehouse;
use App\Models\WarehouseDispatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
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
     * @param  array<int, array{id?: int|null, value?: string|null, min_quantity?: int, quantity_in_stock?: int}>  $variants
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

                $stock = EquipmentStock::query()->firstOrNew([
                    'warehouse_id' => $warehouse->id,
                    'equipment_variant_id' => $variant->id,
                ]);
                if (! $stock->exists) {
                    $stock->quantity_in_stock = 0;
                }
                $stock->min_quantity = (int) ($row['min_quantity'] ?? 0);
                $stock->save();
            }

            $toDelete = $equipment->variants()->whereNotIn('id', $keptIds)->get();
            foreach ($toDelete as $variant) {
                $reason = $variant->deletionBlockReason();
                if ($reason !== null) {
                    throw ValidationException::withMessages([
                        'variants' => "Nie można usunąć rodzaju „{$variant->kind_label}”. {$reason}",
                    ]);
                }
                $variant->delete();
            }

            return $equipment->fresh(['variants.stocks']);
        });
    }

    public function archiveType(Equipment $equipment): void
    {
        if ($equipment->isArchived()) {
            throw ValidationException::withMessages([
                'equipment' => 'Ta pozycja jest już w asortymencie historycznym.',
            ]);
        }

        $equipment->forceFill([
            'is_archived' => true,
            'removed_at' => now(),
        ])->save();
    }

    public function restoreType(Equipment $equipment): void
    {
        if (! $equipment->isArchived()) {
            throw ValidationException::withMessages([
                'equipment' => 'Ta pozycja nie jest w asortymencie historycznym.',
            ]);
        }

        $equipment->forceFill([
            'is_archived' => false,
            'removed_at' => null,
        ])->save();
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
        ?string $notes = null
    ): Collection {
        $entries = collect($this->normalizeIssueLines($lines))
            ->map(fn (array $line) => [
                'employee_id' => $employee->id,
                'variant_id' => $line['variant_id'],
                'quantity' => $line['quantity'],
            ])
            ->all();

        return $this->issueSession($entries, $warehouse, $issueDate, $notes);
    }

    /**
     * Jedna sesja: zlecenie wydania (ZW) rezerwuje sztuki na półce. Magazynier wydaje osobno.
     *
     * @param  array<int, array{employee_id: int, variant_id: int, quantity: int}>  $entries
     * @return Collection<int, EquipmentIssue>
     */
    public function issueSession(
        array $entries,
        Warehouse $warehouse,
        Carbon $issueDate,
        ?string $notes = null
    ): Collection {
        $entries = $this->normalizeSessionEntries($entries);

        if ($entries === []) {
            throw ValidationException::withMessages([
                'lines' => 'Dodaj co najmniej jedną pozycję do zlecenia wydania.',
            ]);
        }

        return DB::transaction(function () use ($entries, $warehouse, $issueDate, $notes) {
            $variantIds = collect($entries)->pluck('variant_id')->unique()->sort()->values();
            $variants = EquipmentVariant::query()
                ->with('equipment')
                ->whereIn('id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            EquipmentStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('equipment_variant_id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $neededByVariant = collect($entries)
                ->groupBy('variant_id')
                ->map(fn (Collection $group) => (int) $group->sum('quantity'));

            foreach ($neededByVariant as $variantId => $needed) {
                $variant = $variants->get((int) $variantId);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        'lines' => 'Wybrany rodzaj nie istnieje.',
                    ]);
                }

                if (! $variant->equipment?->issuable) {
                    throw ValidationException::withMessages([
                        'lines' => "„{$variant->display_name}” nie jest wydawany pracownikom.",
                    ]);
                }

                $available = $variant->availableIn($warehouse);
                if ($available < $needed) {
                    throw ValidationException::withMessages([
                        'lines' => "Niewystarczająca ilość w magazynie „{$warehouse->name}” dla „{$variant->display_name}”. Dostępne: {$available}, żądane: {$needed}.",
                    ]);
                }
            }

            $number = WarehouseDispatch::nextNumber((int) $issueDate->format('Y'));
            $batchId = (string) Str::uuid();
            $dispatch = WarehouseDispatch::create([
                'number' => $number['number'],
                'year' => $number['year'],
                'sequence' => $number['sequence'],
                'warehouse_id' => $warehouse->id,
                'issue_date' => $issueDate,
                'notes' => $notes,
                'status' => WarehouseDispatch::STATUS_RESERVED,
                'created_by' => auth()->id(),
            ]);

            $issues = collect();

            foreach ($entries as $entry) {
                $variant = $variants->get($entry['variant_id']);

                $issues->push(EquipmentIssue::create([
                    'equipment_id' => $variant->equipment_id,
                    'equipment_variant_id' => $variant->id,
                    'warehouse_id' => $warehouse->id,
                    'warehouse_dispatch_id' => $dispatch->id,
                    'employee_id' => $entry['employee_id'],
                    'quantity_issued' => $entry['quantity'],
                    'issue_date' => $issueDate,
                    'status' => EquipmentIssue::STATUS_RESERVED,
                    'notes' => $notes,
                    'batch_id' => $batchId,
                    'issued_by' => auth()->id(),
                ]));
            }

            return $issues->each(fn (EquipmentIssue $issue) => $issue->setRelation('dispatch', $dispatch));
        });
    }

    public function issueAndFulfill(
        Employee $employee,
        EquipmentVariant $variant,
        Warehouse $warehouse,
        int $quantity,
        ?string $notes = null
    ): EquipmentIssue {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Podaj ilość większą od zera.',
            ]);
        }

        return DB::transaction(function () use ($employee, $variant, $warehouse, $quantity, $notes) {
            $issues = $this->issueSession(
                [[
                    'employee_id' => $employee->id,
                    'variant_id' => $variant->id,
                    'quantity' => $quantity,
                ]],
                $warehouse,
                now(),
                $notes
            );

            $issue = $issues->first();
            if (! $issue?->dispatch) {
                throw ValidationException::withMessages([
                    'action' => 'Nie udało się utworzyć zlecenia wydania.',
                ]);
            }

            $this->fulfillDispatch($issue->dispatch, $issues->pluck('id')->all());

            return $issue->fresh(['employee', 'dispatch', 'variant', 'warehouse']);
        });
    }

    /**
     * Wydanie z półki od razu: jedno ZW, kilka wariantów.
     *
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     * @return Collection<int, EquipmentIssue>
     */
    public function issueAndFulfillLines(
        Employee $employee,
        array $lines,
        Warehouse $warehouse,
        ?string $notes = null
    ): Collection {
        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Podaj ilość przy co najmniej jednym wariancie.',
            ]);
        }

        return DB::transaction(function () use ($employee, $lines, $warehouse, $notes) {
            $issues = $this->issueItems($employee, $lines, $warehouse, now(), $notes);
            $dispatch = $issues->first()?->dispatch;

            if (! $dispatch) {
                throw ValidationException::withMessages([
                    'action' => 'Nie udało się utworzyć zlecenia wydania.',
                ]);
            }

            $this->fulfillDispatch($dispatch, $issues->pluck('id')->all());

            return $issues->map(
                fn (EquipmentIssue $issue) => $issue->fresh(['employee', 'dispatch', 'variant', 'warehouse'])
            );
        });
    }

    public function fulfillDispatch(WarehouseDispatch $dispatch, array $issueIds): WarehouseDispatch
    {
        if (! $dispatch->isReserved()) {
            throw ValidationException::withMessages([
                'dispatch' => 'To zlecenie zostało już wydane.',
            ]);
        }

        $issueIds = collect($issueIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        if ($issueIds === []) {
            throw ValidationException::withMessages([
                'issue_ids' => 'Odhacz co najmniej jedną pozycję do wydania.',
            ]);
        }

        return DB::transaction(function () use ($dispatch, $issueIds) {
            /** @var WarehouseDispatch $dispatch */
            $dispatch = WarehouseDispatch::query()
                ->whereKey($dispatch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $dispatch->isReserved()) {
                throw ValidationException::withMessages([
                    'dispatch' => 'To zlecenie zostało już wydane.',
                ]);
            }

            $dispatch->loadMissing('warehouse');

            $issues = $dispatch->issues()
                ->with(['equipment', 'variant'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($issues->isEmpty()) {
                throw ValidationException::withMessages([
                    'dispatch' => 'Zlecenie nie ma pozycji do wydania.',
                ]);
            }

            $selected = $issues
                ->where('status', EquipmentIssue::STATUS_RESERVED)
                ->whereIn('id', $issueIds)
                ->values();

            if ($selected->isEmpty()) {
                throw ValidationException::withMessages([
                    'issue_ids' => 'Odhacz co najmniej jedną pozycję do wydania.',
                ]);
            }

            $variantIds = $selected->pluck('equipment_variant_id')->unique()->sort()->values();
            $stocks = EquipmentStock::query()
                ->where('warehouse_id', $dispatch->warehouse_id)
                ->whereIn('equipment_variant_id', $variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('equipment_variant_id');

            $neededByVariant = $selected
                ->groupBy('equipment_variant_id')
                ->map(fn (Collection $group) => (int) $group->sum('quantity_issued'));

            foreach ($neededByVariant as $variantId => $needed) {
                $stock = $stocks->get((int) $variantId);
                $onHand = (int) ($stock?->quantity_in_stock ?? 0);
                if (! $stock || $onHand < $needed) {
                    $variant = $selected->firstWhere('equipment_variant_id', (int) $variantId)?->variant;
                    $label = $variant?->display_name ?? 'pozycji';
                    throw ValidationException::withMessages([
                        'dispatch' => "Niewystarczająca ilość na półce magazynu „{$dispatch->warehouse?->name}” dla „{$label}”. Na stanie: {$onHand}, do wydania: {$needed}.",
                    ]);
                }

                $stock->decrement('quantity_in_stock', $needed);
            }

            $selectedIds = $selected->pluck('id')->all();
            $skippedCount = $issues->where('status', EquipmentIssue::STATUS_RESERVED)->count() - count($selectedIds);

            foreach ($issues as $issue) {
                if ($issue->status !== EquipmentIssue::STATUS_RESERVED) {
                    continue;
                }

                if (in_array($issue->id, $selectedIds, true)) {
                    $returnable = (bool) $issue->equipment?->returnable;
                    $issue->update([
                        'status' => $returnable ? EquipmentIssue::STATUS_ISSUED : EquipmentIssue::STATUS_GIVEN,
                    ]);

                    continue;
                }

                $issue->update([
                    'status' => EquipmentIssue::STATUS_UNFULFILLED,
                ]);
            }

            $dispatch->update([
                'status' => $skippedCount > 0 ? WarehouseDispatch::STATUS_PARTIAL : WarehouseDispatch::STATUS_ISSUED,
                'issued_at' => now(),
                'issued_by' => auth()->id(),
            ]);

            return $dispatch->fresh(['warehouse.location', 'creator', 'issuer', 'issues.employee', 'issues.equipment', 'issues.variant']);
        });
    }

    /**
     * Przyjęcie (+) albo korekta inwentaryzacyjna (−). Stan nie jest polem katalogu — tylko ruchy.
     */
    public function recordStockMovement(
        EquipmentVariant $variant,
        Warehouse $warehouse,
        StockMovementType $type,
        int $quantity,
        StockMovementReason $reason,
        ?string $notes = null
    ): EquipmentStockMovement {
        if ($type === StockMovementType::RECEIPT) {
            return $this->receiveStock(
                $warehouse,
                [['variant_id' => $variant->id, 'quantity' => $quantity]],
                $reason,
                $notes
            )->first();
        }

        if ($type !== StockMovementType::ADJUSTMENT) {
            throw new \InvalidArgumentException('Ten typ ruchu nie jest obsługiwany jako ręczna korekta stanu.');
        }

        if (! $reason->appliesTo($type)) {
            throw ValidationException::withMessages([
                'reason' => 'Ten powód nie pasuje do wybranego ruchu.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Podaj ilość większą od zera.',
            ]);
        }

        return DB::transaction(function () use ($variant, $warehouse, $type, $quantity, $reason, $notes) {
            /** @var EquipmentVariant $variant */
            $variant = EquipmentVariant::query()
                ->with('equipment')
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($variant->equipment?->isArchived()) {
                throw ValidationException::withMessages([
                    'action' => 'Przywróć pozycję do asortymentu, żeby zmieniać stan.',
                ]);
            }

            $stock = EquipmentStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('equipment_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = EquipmentStock::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'equipment_variant_id' => $variant->id,
                    'quantity_in_stock' => 0,
                    'min_quantity' => 0,
                ]);
                $stock = EquipmentStock::query()
                    ->whereKey($stock->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $onHand = (int) $stock->quantity_in_stock;
            $reserved = $variant->reservedIn($warehouse);
            $available = max(0, $onHand - $reserved);
            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => $reserved > 0
                        ? "W magazynie „{$warehouse->name}” na półce jest {$onHand}, zarezerwowane {$reserved} — można spisać najwyżej {$available}."
                        : "W magazynie „{$warehouse->name}” na półce jest {$onHand}, żądane {$quantity}.",
                ]);
            }
            $stock->decrement('quantity_in_stock', $quantity);

            return EquipmentStockMovement::create([
                'warehouse_id' => $warehouse->id,
                'equipment_id' => $variant->equipment_id,
                'equipment_variant_id' => $variant->id,
                'type' => $type,
                'reason' => $reason,
                'quantity' => $quantity,
                'notes' => filled($notes) ? trim($notes) : null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Przyjęcie wielu wariantów naraz — jeden zapis, jedna transakcja.
     *
     * @param  array<int, array{variant_id?: mixed, quantity?: mixed}>  $lines
     * @return Collection<int, EquipmentStockMovement>
     */
    public function receiveStock(
        Warehouse $warehouse,
        array $lines,
        StockMovementReason $reason,
        ?string $notes = null
    ): Collection {
        if (! $reason->appliesTo(StockMovementType::RECEIPT)) {
            throw ValidationException::withMessages([
                'reason' => 'Ten powód nie pasuje do przyjęcia.',
            ]);
        }

        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'receiptLines' => 'Dodaj co najmniej jeden wariant do przyjęcia.',
            ]);
        }

        return DB::transaction(function () use ($warehouse, $lines, $reason, $notes) {
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
            $notes = filled($notes) ? trim($notes) : null;
            $movements = collect();

            foreach ($lines as $index => $line) {
                $variant = $variants->get($line['variant_id']);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        "receiptLines.{$index}.variant_id" => 'Wybrany rodzaj nie istnieje.',
                    ]);
                }

                if ($variant->equipment?->isArchived()) {
                    throw ValidationException::withMessages([
                        'action' => 'Przywróć pozycję do asortymentu, żeby zmieniać stan.',
                    ]);
                }

                $stock = $stocks->get($variant->id);
                if (! $stock) {
                    $stock = EquipmentStock::query()->create([
                        'warehouse_id' => $warehouse->id,
                        'equipment_variant_id' => $variant->id,
                        'quantity_in_stock' => 0,
                        'min_quantity' => 0,
                    ]);
                    $stock = EquipmentStock::query()
                        ->whereKey($stock->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $stocks->put($variant->id, $stock);
                }

                $stock->increment('quantity_in_stock', $line['quantity']);

                $movements->push(EquipmentStockMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'equipment_id' => $variant->equipment_id,
                    'equipment_variant_id' => $variant->id,
                    'type' => StockMovementType::RECEIPT,
                    'reason' => $reason,
                    'quantity' => $line['quantity'],
                    'notes' => $notes,
                    'batch_id' => $batchId,
                    'created_by' => auth()->id(),
                ]));
            }

            return $movements;
        });
    }

    /**
     * Przerzut magazyn → magazyn: spadek w źródle, wzrost w celu. Opcjonalnie powiązanie ze zdarzeniem logistycznym.
     *
     * @return Collection<int, EquipmentStockMovement>
     */
    public function transferStock(
        EquipmentVariant $variant,
        Warehouse $from,
        Warehouse $to,
        int $quantity,
        ?LogisticsEvent $logisticsEvent = null,
        ?string $notes = null,
        ?string $batchId = null
    ): Collection {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'targetWarehouseId' => 'Wybierz inny magazyn docelowy niż źródłowy.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Podaj ilość większą od zera.',
            ]);
        }

        if ($logisticsEvent && $logisticsEvent->status === LogisticsEventStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'logisticsEventId' => 'Nie można powiązać przemieszczenia z anulowanym zdarzeniem.',
            ]);
        }

        return DB::transaction(function () use ($variant, $from, $to, $quantity, $logisticsEvent, $notes, $batchId) {
            /** @var EquipmentVariant $variant */
            $variant = EquipmentVariant::query()
                ->with('equipment')
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($variant->equipment?->isArchived()) {
                throw ValidationException::withMessages([
                    'action' => 'Przywróć pozycję do asortymentu, żeby zmieniać stan.',
                ]);
            }

            $stocks = $this->lockStocksForTransfer($variant, $from, $to);
            $fromStock = $stocks['from'];
            $toStock = $stocks['to'];

            $onHand = (int) $fromStock->quantity_in_stock;
            $reserved = $variant->reservedIn($from);
            $available = max(0, $onHand - $reserved);

            if ($quantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => $reserved > 0
                        ? "W magazynie „{$from->name}” na półce jest {$onHand}, zarezerwowane {$reserved} — można przemieścić najwyżej {$available}."
                        : "W magazynie „{$from->name}” na półce jest {$onHand}, żądane {$quantity}.",
                ]);
            }

            $fromStock->decrement('quantity_in_stock', $quantity);
            $toStock->increment('quantity_in_stock', $quantity);

            $batchId = filled($batchId) ? $batchId : (string) Str::uuid();
            $notes = filled($notes) ? trim($notes) : null;

            $outbound = EquipmentStockMovement::create([
                'warehouse_id' => $from->id,
                'related_warehouse_id' => $to->id,
                'equipment_id' => $variant->equipment_id,
                'equipment_variant_id' => $variant->id,
                'type' => StockMovementType::TRANSFER_OUT,
                'quantity' => $quantity,
                'notes' => $notes,
                'batch_id' => $batchId,
                'logistics_event_id' => $logisticsEvent?->id,
                'created_by' => auth()->id(),
            ]);

            $inbound = EquipmentStockMovement::create([
                'warehouse_id' => $to->id,
                'related_warehouse_id' => $from->id,
                'equipment_id' => $variant->equipment_id,
                'equipment_variant_id' => $variant->id,
                'type' => StockMovementType::TRANSFER_IN,
                'quantity' => $quantity,
                'notes' => $notes,
                'batch_id' => $batchId,
                'logistics_event_id' => $logisticsEvent?->id,
                'created_by' => auth()->id(),
            ]);

            return collect([$outbound, $inbound]);
        });
    }

    /**
     * Kilka pozycji w jednym przemieszczeniu (wspólny batch_id).
     *
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     * @return Collection<int, EquipmentStockMovement>
     */
    public function transferStockLines(
        Warehouse $from,
        Warehouse $to,
        array $lines,
        ?LogisticsEvent $logisticsEvent = null,
        ?string $notes = null
    ): Collection {
        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Dodaj co najmniej jedną pozycję do przemieszczenia.',
            ]);
        }

        usort($lines, fn (array $left, array $right) => $left['variant_id'] <=> $right['variant_id']);

        return DB::transaction(function () use ($from, $to, $lines, $logisticsEvent, $notes) {
            $batchId = (string) Str::uuid();
            $movements = collect();

            foreach ($lines as $line) {
                $variant = EquipmentVariant::query()->findOrFail($line['variant_id']);
                $movements = $movements->concat(
                    $this->transferStock(
                        $variant,
                        $from,
                        $to,
                        $line['quantity'],
                        $logisticsEvent,
                        $notes,
                        $batchId
                    )
                );
            }

            return $movements;
        });
    }

    /**
     * Rozchód: zdejmuje stan od razu. Nie tworzy wydania na pracownika.
     * Przeznaczenie (osoba / projekt / dom / auto) zapisujemy polimorficznie.
     *
     * @param  array<int, array{variant_id: int, quantity: int}>  $lines
     * @return Collection<int, EquipmentStockMovement>
     */
    public function consumeItems(
        array $lines,
        Warehouse $warehouse,
        ?Model $consumedFor = null,
        ?string $notes = null
    ): Collection {
        $lines = $this->normalizeIssueLines($lines);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Dodaj co najmniej jedną pozycję do rozchodu.',
            ]);
        }

        if ($consumedFor && ! ConsumptionDestination::tryFromModel($consumedFor)) {
            throw ValidationException::withMessages([
                'destinationType' => 'Wybierz przeznaczenie rozchodu: osobę, projekt, dom albo auto.',
            ]);
        }

        return DB::transaction(function () use ($lines, $warehouse, $consumedFor, $notes) {
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
                    'employee_id' => $consumedFor instanceof Employee ? $consumedFor->id : null,
                    'consumed_for_type' => $consumedFor?->getMorphClass(),
                    'consumed_for_id' => $consumedFor?->getKey(),
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
        ?string $notes = null
    ): EquipmentIssue {
        return $this->issueItems(
            $employee,
            [['variant_id' => $variant->id, 'quantity' => $quantityIssued]],
            $warehouse,
            $issueDate,
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

        if (! $equipmentIssue->isReturnableIssue()) {
            throw ValidationException::withMessages([
                'equipment' => $equipmentIssue->isReserved()
                    ? 'Najpierw wydaj sprzęt z magazynu. Zwrot dotyczy tylko wydanych pozycji.'
                    : 'Ta pozycja nie podlega zwrotowi.',
            ]);
        }

        if (! $equipmentIssue->equipment->issuable || ! $equipmentIssue->equipment->returnable) {
            throw ValidationException::withMessages([
                'equipment' => 'Ta pozycja nie może być zwracana, zgłaszana jako uszkodzona lub zgubiona.',
            ]);
        }

        return DB::transaction(function () use ($equipmentIssue, $returnDate, $status, $notes) {
            /** @var EquipmentIssue $equipmentIssue */
            $equipmentIssue = EquipmentIssue::query()
                ->whereKey($equipmentIssue->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $equipmentIssue->isReturnableIssue()) {
                throw ValidationException::withMessages([
                    'equipment' => 'Pozycja została już zwrócona, zgłoszona jako uszkodzona lub zgubiona.',
                ]);
            }

            $equipmentIssue->markAsReturned($returnDate, auth()->id(), $status);

            if ($notes !== null) {
                $equipmentIssue->update(['notes' => $notes]);
            }

            if ($status === EquipmentIssue::STATUS_RETURNED) {
                $stock = EquipmentStock::query()
                    ->where('warehouse_id', $equipmentIssue->warehouse_id)
                    ->where('equipment_variant_id', $equipmentIssue->equipment_variant_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->increment('quantity_in_stock', $equipmentIssue->quantity_issued);
                } else {
                    EquipmentStock::query()->create([
                        'warehouse_id' => $equipmentIssue->warehouse_id,
                        'equipment_variant_id' => $equipmentIssue->equipment_variant_id,
                        'quantity_in_stock' => $equipmentIssue->quantity_issued,
                        'min_quantity' => 0,
                    ]);
                }
            }

            return true;
        });
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

    /**
     * @param  array<int, array{employee_id?: mixed, variant_id?: mixed, quantity?: mixed}>  $entries
     * @return array<int, array{employee_id: int, variant_id: int, quantity: int}>
     */
    private function normalizeSessionEntries(array $entries): array
    {
        $merged = [];

        foreach ($entries as $entry) {
            $employeeId = (int) ($entry['employee_id'] ?? 0);
            $variantId = (int) ($entry['variant_id'] ?? 0);
            $quantity = (int) ($entry['quantity'] ?? 0);
            if ($employeeId < 1 || $variantId < 1 || $quantity < 1) {
                continue;
            }
            $key = $employeeId.':'.$variantId;
            $merged[$key] = [
                'employee_id' => $employeeId,
                'variant_id' => $variantId,
                'quantity' => ($merged[$key]['quantity'] ?? 0) + $quantity,
            ];
        }

        return array_values($merged);
    }

    /**
     * Przyjęcia i rozchody tej pozycji z ostatnich dni — do wykresu na karcie produktu.
     *
     * @return array{labels: list<string>, inbound: list<int>, outbound: list<int>, inbound_total: int, outbound_total: int, days: int}
     */
    public function stockMovementChart(Equipment $equipment, int $days = 30): array
    {
        $days = max(1, $days);
        $end = now()->startOfDay();
        $start = $end->copy()->subDays($days - 1);

        $inbound = [];
        $outbound = [];
        $labels = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('d.m');
            $inbound[$key] = 0;
            $outbound[$key] = 0;
        }

        $add = function (array &$bucket, $at, int $quantity) use ($start): void {
            if ($quantity < 1 || ! $at) {
                return;
            }

            $at = Carbon::parse($at);
            if ($at->lt($start)) {
                return;
            }

            $key = $at->toDateString();
            if (array_key_exists($key, $bucket)) {
                $bucket[$key] += $quantity;
            }
        };

        EquipmentStockMovement::query()
            ->where('equipment_id', $equipment->id)
            ->whereIn('type', [
                StockMovementType::RECEIPT,
                StockMovementType::CONSUMPTION,
                StockMovementType::ADJUSTMENT,
            ])
            ->where('created_at', '>=', $start)
            ->get(['created_at', 'quantity', 'type'])
            ->each(function (EquipmentStockMovement $movement) use (&$inbound, &$outbound, $add): void {
                if ($movement->type === StockMovementType::RECEIPT) {
                    $add($inbound, $movement->created_at, (int) $movement->quantity);

                    return;
                }

                $add($outbound, $movement->created_at, (int) $movement->quantity);
            });

        EquipmentIssue::query()
            ->where('equipment_id', $equipment->id)
            ->whereNotIn('status', [EquipmentIssue::STATUS_RESERVED, EquipmentIssue::STATUS_UNFULFILLED])
            ->with('dispatch')
            ->get()
            ->each(function (EquipmentIssue $issue) use (&$inbound, &$outbound, $add): void {
                $issuedAt = $issue->dispatch?->issued_at ?? $issue->created_at;
                $add($outbound, $issuedAt, (int) $issue->quantity_issued);

                $closedAt = $issue->actual_return_date ?? $issue->updated_at;
                if ($issue->status === EquipmentIssue::STATUS_RETURNED) {
                    $add($inbound, $closedAt, (int) $issue->quantity_issued);

                    return;
                }

                if (in_array($issue->status, [EquipmentIssue::STATUS_DAMAGED, EquipmentIssue::STATUS_LOST], true)) {
                    $add($outbound, $closedAt, (int) $issue->quantity_issued);
                }
            });

        $inboundValues = array_values($inbound);
        $outboundValues = array_values($outbound);

        return [
            'labels' => $labels,
            'inbound' => $inboundValues,
            'outbound' => $outboundValues,
            'inbound_total' => array_sum($inboundValues),
            'outbound_total' => array_sum($outboundValues),
            'days' => $days,
        ];
    }

    /**
     * @return Collection<int, array{occurred_at: ?Carbon, title: string, meta: string, notes: ?string, signed_quantity: int, quantity_label: string, dot_color: string}>
     */
    public function stockTimeline(Equipment $equipment): Collection
    {
        $fromMovements = EquipmentStockMovement::query()
            ->where('equipment_id', $equipment->id)
            ->with([
                'variant.equipment',
                'warehouse.location',
                'relatedWarehouse.location',
                'creator',
                'employee',
                'consumedFor',
                'logisticsEvent.fromLocation',
                'logisticsEvent.toLocation',
            ])
            ->orderBy('id')
            ->get()
            ->groupBy(function (EquipmentStockMovement $movement) {
                if ($movement->type?->isTransfer() && filled($movement->batch_id)) {
                    return 'transfer:'.$movement->batch_id;
                }
                if ($movement->type === StockMovementType::RECEIPT && filled($movement->batch_id)) {
                    return 'receipt:'.$movement->batch_id;
                }
                if ($movement->type === StockMovementType::CONSUMPTION && filled($movement->batch_id)) {
                    return 'consumption:'.$movement->batch_id;
                }

                return 'movement:'.$movement->id;
            })
            ->map(function (Collection $group) {
                $first = $group->first();
                if ($first->type?->isTransfer()) {
                    return $this->timelineRowFromTransfer($group);
                }
                if ($first->type === StockMovementType::RECEIPT && $group->count() > 1) {
                    return $this->timelineRowFromReceipt($group);
                }
                if ($first->type === StockMovementType::CONSUMPTION && $group->count() > 1) {
                    return $this->timelineRowFromConsumption($group);
                }

                $signed = $first->signedQuantity();

                return [
                    'occurred_at' => $first->created_at,
                    'title' => $first->title(),
                    'meta' => $first->metaLine(),
                    'notes' => $first->notes,
                    'signed_quantity' => $signed,
                    'quantity_label' => $first->quantityLabel(),
                    'dot_color' => $first->type?->dotColor() ?? '#64748b',
                ];
            })
            ->values();

        $fromIssues = $this->timelineFromIssues($equipment);

        return $fromMovements
            ->concat($fromIssues)
            ->sortByDesc(fn (array $row) => $row['occurred_at']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @return Collection<int, array{occurred_at: ?Carbon, title: string, meta: string, notes: ?string, signed_quantity: int, quantity_label: string, dot_color: string, href?: string, lines?: list<array{employee: string, sku: string, quantity: int}>}>
     */
    private function timelineFromIssues(Equipment $equipment): Collection
    {
        $issues = EquipmentIssue::query()
            ->where('equipment_id', $equipment->id)
            ->whereNotIn('status', [EquipmentIssue::STATUS_RESERVED, EquipmentIssue::STATUS_UNFULFILLED])
            ->with(['variant.equipment', 'warehouse.location', 'employee', 'dispatch.issuer', 'issuer', 'returner'])
            ->get();

        $issuedRows = $issues
            ->groupBy(fn (EquipmentIssue $issue) => $issue->warehouse_dispatch_id ?: 'issue-'.$issue->id)
            ->map(function (Collection $group) {
                $first = $group->first();
                $qty = (int) $group->sum('quantity_issued');
                $issuedAt = $first->dispatch?->issued_at ?? $first->created_at;
                $issuerName = $first->dispatch?->issuer?->name
                    ?? $first->issuer?->name;

                $lines = $group
                    ->sortBy(fn (EquipmentIssue $issue) => $issue->employee?->last_name.' '.$issue->employee?->first_name)
                    ->map(fn (EquipmentIssue $issue) => [
                        'employee' => $issue->employee?->full_name ?? '—',
                        'sku' => $issue->variant?->sku ?? $issue->equipment?->name ?? '—',
                        'quantity' => (int) $issue->quantity_issued,
                    ])
                    ->values()
                    ->all();

                $row = [
                    'occurred_at' => $issuedAt,
                    'title' => $first->dispatch
                        ? 'Wydanie na zlecenie '.$first->dispatch->number
                        : $first->eventLabel(),
                    'meta' => collect([
                        $first->warehouse?->display_name,
                        $issuerName,
                        EquipmentStockMovement::formatHappenedAt($issuedAt),
                    ])->filter()->unique()->implode(' · '),
                    'notes' => $first->dispatch?->notes ?: $first->notes,
                    'signed_quantity' => -$qty,
                    'quantity_label' => '-'.$qty.' szt.',
                    'dot_color' => '#f43f5e',
                    'lines' => $lines,
                ];

                if ($first->dispatch) {
                    $row['href'] = route('warehouse-dispatches.show', $first->dispatch);
                }

                return $row;
            })
            ->values();

        $returnRows = $issues
            ->filter(fn (EquipmentIssue $issue) => in_array($issue->status, [
                EquipmentIssue::STATUS_RETURNED,
                EquipmentIssue::STATUS_DAMAGED,
                EquipmentIssue::STATUS_LOST,
            ], true))
            ->map(function (EquipmentIssue $issue) {
                $sku = $issue->variant?->sku ?? $issue->equipment?->name;
                $qty = (int) $issue->quantity_issued;
                $closedAt = $issue->updated_at ?? $issue->actual_return_date;
                $returned = $issue->status === EquipmentIssue::STATUS_RETURNED;

                return [
                    'occurred_at' => $closedAt,
                    'title' => match ($issue->status) {
                        EquipmentIssue::STATUS_DAMAGED => 'Uszkodzenie',
                        EquipmentIssue::STATUS_LOST => 'Zgubienie',
                        default => 'Zwrot',
                    },
                    'meta' => collect([
                        $sku,
                        $issue->warehouse?->display_name,
                        $issue->employee?->full_name,
                        $issue->returner?->name,
                        EquipmentStockMovement::formatHappenedAt($closedAt),
                    ])->filter()->unique()->implode(' · '),
                    'notes' => $issue->notes,
                    'signed_quantity' => $returned ? $qty : 0,
                    'quantity_label' => $returned ? '+'.$qty.' szt.' : $qty.' szt.',
                    'dot_color' => $returned ? '#14b8a6' : '#f59e0b',
                    'lines' => [],
                    'href' => route('equipment-issues.show', $issue),
                ];
            })
            ->values();

        return $issuedRows->concat($returnRows);
    }

    /**
     * @return array{from: EquipmentStock, to: EquipmentStock}
     */
    private function lockStocksForTransfer(EquipmentVariant $variant, Warehouse $from, Warehouse $to): array
    {
        $ordered = collect([$from, $to])->sortBy('id')->values();
        $locked = [];

        foreach ($ordered as $warehouse) {
            $createIfMissing = $warehouse->id === $to->id;
            $stock = EquipmentStock::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('equipment_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            if (! $stock && $createIfMissing) {
                $stock = EquipmentStock::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'equipment_variant_id' => $variant->id,
                    'quantity_in_stock' => 0,
                    'min_quantity' => 0,
                ]);
                $stock = EquipmentStock::query()
                    ->whereKey($stock->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $locked[$warehouse->id] = $stock;
        }

        $fromStock = $locked[$from->id] ?? null;
        $toStock = $locked[$to->id] ?? null;

        if (! $fromStock) {
            throw ValidationException::withMessages([
                'quantity' => "W magazynie „{$from->name}” nie ma tej pozycji na półce.",
            ]);
        }

        if (! $toStock) {
            throw ValidationException::withMessages([
                'targetWarehouseId' => 'Nie udało się otworzyć stanu w magazynie docelowym.',
            ]);
        }

        return [
            'from' => $fromStock,
            'to' => $toStock,
        ];
    }

    /**
     * @param  Collection<int, EquipmentStockMovement>  $group
     * @return array{occurred_at: mixed, title: string, meta: string, notes: ?string, signed_quantity: int, quantity_label: string, dot_color: string, href?: string, lines?: list<array{employee: string, sku: string, quantity: int}>}
     */
    private function timelineRowFromTransfer(Collection $group): array
    {
        $outbounds = $group->where('type', StockMovementType::TRANSFER_OUT)->values();
        $outbound = $outbounds->first() ?? $group->first();
        $inbound = $group->firstWhere('type', StockMovementType::TRANSFER_IN);
        $fromWarehouse = $outbound->warehouse;
        $toWarehouse = $inbound?->warehouse ?? $outbound->relatedWarehouse;
        $qty = (int) $outbounds->sum('quantity');
        $event = $outbound->logisticsEvent ?? $inbound?->logisticsEvent;
        $lines = $outbounds->count() > 1
            ? $outbounds->map(fn (EquipmentStockMovement $movement) => [
                'employee' => $movement->variant?->kind_label ?? '—',
                'sku' => $movement->variant?->sku ?? $movement->equipment?->name ?? '—',
                'quantity' => (int) $movement->quantity,
            ])->values()->all()
            : [];

        $row = [
            'occurred_at' => $outbound->created_at,
            'title' => 'Przemieszczenie',
            'meta' => collect([
                $outbounds->count() === 1
                    ? ($outbound->variant?->sku ?? $outbound->equipment?->name)
                    : null,
                collect([$fromWarehouse?->display_name, $toWarehouse?->display_name])->filter()->implode(' → '),
                $event ? $this->logisticsEventLabel($event) : null,
                $outbound->creator?->name,
                EquipmentStockMovement::formatHappenedAt($outbound->created_at),
            ])->filter()->unique()->implode(' · '),
            'notes' => $outbound->notes ?: $inbound?->notes,
            'signed_quantity' => 0,
            'quantity_label' => $qty.' szt.',
            'dot_color' => StockMovementType::TRANSFER_OUT->dotColor(),
            'lines' => $lines,
        ];

        if ($event) {
            $href = $this->logisticsEventHref($event);
            if ($href) {
                $row['href'] = $href;
            }
        }

        return $row;
    }

    /**
     * @param  Collection<int, EquipmentStockMovement>  $group
     * @return array{occurred_at: mixed, title: string, meta: string, notes: ?string, signed_quantity: int, quantity_label: string, dot_color: string, lines: list<array{employee: string, sku: string, quantity: int}>}
     */
    private function timelineRowFromReceipt(Collection $group): array
    {
        $first = $group->first();
        $qty = (int) $group->sum('quantity');
        $lines = $group
            ->map(fn (EquipmentStockMovement $movement) => [
                'employee' => $movement->variant?->kind_label ?? '—',
                'sku' => $movement->variant?->sku ?? $movement->equipment?->name ?? '—',
                'quantity' => (int) $movement->quantity,
            ])
            ->values()
            ->all();

        return [
            'occurred_at' => $first->created_at,
            'title' => $first->title(),
            'meta' => collect([
                $first->warehouse?->display_name,
                $first->creator?->name,
                EquipmentStockMovement::formatHappenedAt($first->created_at),
            ])->filter()->unique()->implode(' · '),
            'notes' => $first->notes,
            'signed_quantity' => $qty,
            'quantity_label' => '+'.$qty.' szt.',
            'dot_color' => StockMovementType::RECEIPT->dotColor(),
            'lines' => $lines,
        ];
    }

    /**
     * @param  Collection<int, EquipmentStockMovement>  $group
     * @return array{occurred_at: mixed, title: string, meta: string, notes: ?string, signed_quantity: int, quantity_label: string, dot_color: string, href?: string, lines: list<array{employee: string, sku: string, quantity: int}>}
     */
    private function timelineRowFromConsumption(Collection $group): array
    {
        $first = $group->first();
        $qty = (int) $group->sum('quantity');
        $lines = $group
            ->map(fn (EquipmentStockMovement $movement) => [
                'employee' => $movement->variant?->kind_label ?? '—',
                'sku' => $movement->variant?->sku ?? $movement->equipment?->name ?? '—',
                'quantity' => (int) $movement->quantity,
            ])
            ->values()
            ->all();

        $row = [
            'occurred_at' => $first->created_at,
            'title' => $first->title(),
            'meta' => collect([
                $first->destinationMeta(),
                $first->warehouse?->display_name,
                $first->creator?->name,
                EquipmentStockMovement::formatHappenedAt($first->created_at),
            ])->filter()->unique()->implode(' · '),
            'notes' => $first->notes,
            'signed_quantity' => -$qty,
            'quantity_label' => '-'.$qty.' szt.',
            'dot_color' => StockMovementType::CONSUMPTION->dotColor(),
            'lines' => $lines,
        ];

        $href = $first->destinationHref();
        if ($href) {
            $row['href'] = $href;
        }

        return $row;
    }

    public function logisticsEventLabel(LogisticsEvent $event): string
    {
        $event->loadMissing(['fromLocation', 'toLocation']);

        return collect([
            $event->type?->label().' #'.$event->id,
            $event->event_date?->format('Y-m-d'),
            collect([$event->fromLocation?->name, $event->toLocation?->name])->filter()->implode(' → '),
        ])->filter()->implode(' · ');
    }

    private function logisticsEventHref(LogisticsEvent $event): ?string
    {
        return match ($event->type) {
            LogisticsEventType::DEPARTURE => route('departures.show', $event),
            LogisticsEventType::RETURN => route('return-trips.show', $event),
            LogisticsEventType::TRANSFER => route('transfers.show', $event),
            default => null,
        };
    }
}
