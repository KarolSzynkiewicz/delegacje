<?php

namespace App\Console\Commands;

use App\Services\WorkItemSync;
use Illuminate\Console\Command;

class SyncWorkItemsCommand extends Command
{
    protected $signature = 'work-items:sync';

    protected $description = 'Przebuduj indeks backlogu (work_items) ze źródeł: zadania, podzadania, procedury, ZW, wzmianki, zatwierdzenia';

    public function handle(WorkItemSync $sync): int
    {
        $this->info('Synchronizuję work items…');
        $count = $sync->backfill();
        $this->info("Zapisano {$count} wierszy backlogu.");

        return self::SUCCESS;
    }
}
