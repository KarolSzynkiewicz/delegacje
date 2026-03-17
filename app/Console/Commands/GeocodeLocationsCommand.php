<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:geocode {--all : Geocode all locations without coordinates} {--id= : Geocode specific location by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode locations (convert addresses to coordinates)';

    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geocodingService)
    {
        $this->info('Starting geocoding process...');

        if ($this->option('id')) {
            $location = Location::find($this->option('id'));
            if (!$location) {
                $this->error("Location with ID {$this->option('id')} not found.");
                return 1;
            }

            $this->geocodeLocation($location, $geocodingService);
            return 0;
        }

        if ($this->option('all')) {
            $locations = Location::whereNull('latitude')
                ->orWhereNull('longitude')
                ->get();

            if ($locations->isEmpty()) {
                $this->info('All locations already have coordinates.');
                return 0;
            }

            $this->info("Found {$locations->count()} locations without coordinates.");
            
            if (!$this->confirm('Do you want to geocode all of them?', true)) {
                $this->info('Cancelled.');
                return 0;
            }

            $bar = $this->output->createProgressBar($locations->count());
            $bar->start();

            $successCount = 0;
            foreach ($locations as $location) {
                if ($this->geocodeLocation($location, $geocodingService, false)) {
                    $successCount++;
                }
                $bar->advance();
                
                // Rate limiting: wait 100ms between requests
                usleep(100000);
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Successfully geocoded {$successCount} out of {$locations->count()} locations.");
            return 0;
        }

        // Default: show locations without coordinates
        $locations = Location::whereNull('latitude')
            ->orWhereNull('longitude')
            ->get();

        if ($locations->isEmpty()) {
            $this->info('All locations have coordinates.');
            return 0;
        }

        $this->info("Found {$locations->count()} locations without coordinates:");
        $this->table(
            ['ID', 'Name', 'Address', 'City'],
            $locations->map(fn($l) => [
                $l->id,
                $l->name,
                $l->address,
                $l->city ?? '-',
            ])->toArray()
        );

        $this->info("\nUse --all to geocode all locations, or --id=<location_id> to geocode specific location.");
        return 0;
    }

    protected function geocodeLocation(Location $location, GeocodingService $geocodingService, bool $verbose = true): bool
    {
        if ($location->hasCoordinates()) {
            if ($verbose) {
                $this->warn("Location '{$location->name}' already has coordinates.");
            }
            return true;
        }

        $address = $location->getFullAddress();
        if (empty($address)) {
            if ($verbose) {
                $this->error("Location '{$location->name}' has no address to geocode.");
            }
            return false;
        }

        if ($verbose) {
            $this->info("Geocoding: {$address}");
        }

        $success = $geocodingService->geocodeLocation($location);
        
        if ($success && $verbose) {
            $this->info("  ✓ Coordinates: {$location->latitude}, {$location->longitude}");
        } elseif ($verbose) {
            $this->error("  ✗ Failed to geocode");
        }

        return $success;
    }
}
