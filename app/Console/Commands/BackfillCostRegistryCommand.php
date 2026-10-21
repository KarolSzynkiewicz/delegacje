<?php

namespace App\Console\Commands;

use App\Models\CostEntry;
use App\Services\CostRegistryService;
use Illuminate\Console\Command;

/**
 * Dopisuje do rejestru kosztów brakujące otoczki dla istniejących pozycji
 * źródłowych. Migracja robi to raz; komenda jest na wypadek importów
 * omijających Eloquent (np. SQL) albo ręcznego czyszczenia tabeli.
 */
class BackfillCostRegistryCommand extends Command
{
    protected $signature = 'cost-registry:backfill';

    protected $description = 'Dopisz do rejestru kosztów brakujące pozycje dla istniejących kosztów źródłowych';

    public function handle(CostRegistryService $registry): int
    {
        $created = $registry->backfill();
        $labels = CostEntry::sourceTypeOptions();

        foreach ($created as $alias => $count) {
            $this->line(sprintf('%-16s +%d', $labels[$alias] ?? $alias, $count));
        }

        $this->info('Rejestr kosztów: '.CostEntry::query()->count().' pozycji.');

        return self::SUCCESS;
    }
}
