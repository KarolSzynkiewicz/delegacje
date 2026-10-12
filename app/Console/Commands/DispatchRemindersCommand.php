<?php

namespace App\Console\Commands;

use App\Support\ReminderDispatcher;
use Illuminate\Console\Command;

class DispatchRemindersCommand extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Wyślij przypomnienia o blokach czasu i terminach';

    public function handle(ReminderDispatcher $reminders): int
    {
        $count = $reminders->dispatch();
        $this->info("Wysłano {$count} przypomnień.");

        return self::SUCCESS;
    }
}
