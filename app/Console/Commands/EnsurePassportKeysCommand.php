<?php

namespace App\Console\Commands;

use App\Mcp\Support\PassportKeyStore;
use Illuminate\Console\Command;

class EnsurePassportKeysCommand extends Command
{
    protected $signature = 'mcp:ensure-passport-keys';

    protected $description = 'Ensure Passport OAuth keys survive Railway restarts (DB-backed)';

    public function handle(): int
    {
        $source = PassportKeyStore::ensure();

        match ($source) {
            'env' => $this->info('Using PASSPORT_PRIVATE_KEY / PASSPORT_PUBLIC_KEY from the environment.'),
            'files' => $this->info('Passport keys loaded from files and stored in the database.'),
            'database' => $this->info('Passport keys restored from the database.'),
            'generated' => $this->warn('Generated new Passport keys and stored them in the database.'),
        };

        return self::SUCCESS;
    }
}
