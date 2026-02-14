<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LogisticsEvent;
use App\Enums\LogisticsEventType;

class FixDepartureEndDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:departure-end-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Naprawia stare wyjazdy bez end_date (dodaje +1 dzień od event_date)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $departures = LogisticsEvent::where('type', LogisticsEventType::DEPARTURE)
            ->whereNull('end_date')
            ->get();

        if ($departures->isEmpty()) {
            $this->info('✅ Wszystkie wyjazdy mają już end_date.');
            return 0;
        }

        $this->info("Znaleziono {$departures->count()} wyjazdów bez end_date");
        
        $bar = $this->output->createProgressBar($departures->count());
        $bar->start();

        foreach ($departures as $departure) {
            $newEndDate = $departure->event_date->copy()->addDay();
            $departure->update(['end_date' => $newEndDate]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Naprawiono {$departures->count()} wyjazdów!");

        return 0;
    }
}
