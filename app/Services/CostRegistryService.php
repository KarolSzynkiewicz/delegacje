<?php

namespace App\Services;

use App\Models\CostEntry;
use App\Models\FixedCostEntry;
use App\Models\ProjectVariableCost;
use App\Models\TransportCost;
use App\Models\VehicleRepair;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Utrzymuje rejestr kosztów ({@see CostEntry}) w zgodzie z tabelami źródłowymi.
 *
 * Zasady:
 * - Jedna pozycja źródłowa = jedna otoczka. Powtórny zapis źródła nie nadpisuje
 *   nazwy ani miesiąca w rejestrze (są własnością osoby prowadzącej rejestr);
 *   kwota, waluta i data nie są kopiowane, więc zawsze są świeże.
 * - Zakończona naprawa pojazdu tworzy kopię w `fixed_cost_entries`
 *   ({@see VehicleRepairService}). Do rejestru wchodzi NAPRAWA, a wpis stały,
 *   na który wskazuje `vehicle_repairs.fixed_cost_entry_id`, jest pomijany —
 *   inaczej ten sam rachunek byłby w tablicy dwa razy.
 * - Naprawa bez ceny nie jest kosztem i otoczki nie dostaje.
 */
class CostRegistryService
{
    /**
     * Dopisz brakującą otoczkę po zapisie źródła (create/update).
     */
    public function syncFor(Model $source): ?CostEntry
    {
        if ($source instanceof VehicleRepair) {
            return $this->syncRepair($source);
        }

        if ($source instanceof FixedCostEntry) {
            return $this->syncFixedCost($source);
        }

        if ($source instanceof ProjectVariableCost || $source instanceof TransportCost) {
            return $this->ensureEntry($source);
        }

        return null;
    }

    public function deleteFor(Model $source): void
    {
        CostEntry::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->delete();
    }

    /**
     * Dopisz otoczki dla wszystkich istniejących kosztów, które jeszcze ich nie mają.
     *
     * @return array<string, int> alias → liczba utworzonych
     */
    public function backfill(): array
    {
        $created = [];

        foreach (CostEntry::sourceModels() as $alias => $class) {
            $created[$alias] = 0;

            $query = $class::query();
            if ($class === VehicleRepair::class) {
                $query->with('vehicle');
            }

            $query->chunkById(200, function ($rows) use (&$created, $alias) {
                foreach ($rows as $row) {
                    $before = CostEntry::query()
                        ->where('source_type', $row->getMorphClass())
                        ->where('source_id', $row->getKey())
                        ->exists();

                    if ($this->syncFor($row) && ! $before) {
                        $created[$alias]++;
                    }
                }
            });
        }

        return $created;
    }

    private function syncRepair(VehicleRepair $repair): ?CostEntry
    {
        // Kopia w kosztach stałych nie może wejść do rejestru obok naprawy.
        if ($repair->fixed_cost_entry_id) {
            CostEntry::query()
                ->where('source_type', CostEntry::SOURCE_FIXED)
                ->where('source_id', $repair->fixed_cost_entry_id)
                ->delete();
        }

        if ($repair->price === null) {
            return $this->existing($repair);
        }

        return $this->ensureEntry($repair);
    }

    private function syncFixedCost(FixedCostEntry $entry): ?CostEntry
    {
        if (VehicleRepair::query()->where('fixed_cost_entry_id', $entry->id)->exists()) {
            $this->deleteFor($entry);

            return null;
        }

        return $this->ensureEntry($entry);
    }

    private function existing(Model $source): ?CostEntry
    {
        return CostEntry::query()
            ->where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->first();
    }

    private function ensureEntry(Model $source): CostEntry
    {
        $entry = $this->existing($source);
        if ($entry) {
            return $entry;
        }

        $date = $this->defaultDate($source) ?? Carbon::today();

        return CostEntry::create([
            'source_type' => $source->getMorphClass(),
            'source_id' => $source->getKey(),
            'company_id' => null,
            'name' => $this->defaultName($source),
            'period_year' => (int) $date->format('Y'),
            'period_month' => (int) $date->format('n'),
            'financially_irrelevant' => false,
            'comment' => null,
        ]);
    }

    public function defaultName(Model $source): string
    {
        $name = match (true) {
            $source instanceof FixedCostEntry => $source->name,
            $source instanceof ProjectVariableCost => $source->name,
            $source instanceof TransportCost => $source->description
                ?: (TransportCost::costTypeLabels()[$source->cost_type] ?? $source->cost_type),
            $source instanceof VehicleRepair => 'Serwis '.($source->vehicle?->registration_number ?? '#'.$source->id),
            default => class_basename($source).' #'.$source->getKey(),
        };

        $name = trim((string) $name);

        return $name !== '' ? mb_substr($name, 0, 255) : class_basename($source).' #'.$source->getKey();
    }

    /**
     * Miesiąc rozliczeniowy na start: data księgowania / poniesienia / kosztu / końca naprawy.
     */
    public function defaultDate(Model $source): ?CarbonInterface
    {
        $date = match (true) {
            $source instanceof FixedCostEntry => $source->accounting_date ?? $source->period_start,
            $source instanceof ProjectVariableCost => $source->incurred_date ?? $source->created_at,
            $source instanceof TransportCost => $source->cost_date,
            $source instanceof VehicleRepair => $source->end_date ?? $source->start_date,
            default => null,
        };

        return $date ? Carbon::parse($date) : null;
    }
}
