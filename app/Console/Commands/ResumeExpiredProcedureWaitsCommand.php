<?php

namespace App\Console\Commands;

use App\Services\ProcedureRunService;
use Illuminate\Console\Command;

class ResumeExpiredProcedureWaitsCommand extends Command
{
    protected $signature = 'procedures:resume-waits';

    protected $description = 'Wznów kroki oczekiwania, których czas minął';

    public function handle(ProcedureRunService $runs): int
    {
        $count = $runs->resumeExpiredWaits();
        $this->info("Wznowiono {$count} oczekiwania.");

        return self::SUCCESS;
    }
}
