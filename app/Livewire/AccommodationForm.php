<?php

namespace App\Livewire;

use App\Models\Accommodation;
use App\Models\Location;
use App\Services\GeocodingService;
use Livewire\Component;

class AccommodationForm extends Component
{
    // Location picker mode: 'existing' | 'new'
    public string $location_mode = 'new';

    // Existing location selection
    public ?int $location_id = null;

    // New location geo-search fields
    public string $searchQuery = '';

    public array $searchResults = [];

    public bool $isSearching = false;

    public bool $showResults = false;

    public ?string $searchError = null;

    public string $location_name = '';

    public string $address = '';

    public string $city = '';

    public string $postal_code = '';

    public string $country = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    // Accommodation-specific fields
    public string $name = '';

    public int $capacity = 1;

    public string $type = 'wynajmowany';

    public ?string $lease_start_date = null;

    public ?string $lease_end_date = null;

    public ?string $lease_monthly_rent = null;

    public string $lease_currency = 'EUR';

    public string $description = '';

    protected GeocodingService $geocodingService;

    public function boot(GeocodingService $geocodingService): void
    {
        $this->geocodingService = $geocodingService;
    }

    public function mount(?Accommodation $accommodation = null): void
    {
        if ($accommodation && $accommodation->exists) {
            $this->name = $accommodation->name;
            $this->capacity = $accommodation->capacity ?? 1;
            $this->description = $accommodation->description ?? '';

            $lease = $accommodation->relationLoaded('activeLease')
                ? $accommodation->activeLease
                : $accommodation->activeLease()->first();

            $this->type = $lease?->type ?? 'własny';
            $this->lease_start_date = $lease?->start_date?->format('Y-m-d');
            $this->lease_end_date = $lease?->end_date?->format('Y-m-d');
            $this->lease_monthly_rent = $lease?->monthly_rent !== null ? (string) $lease->monthly_rent : null;
            $this->lease_currency = $lease?->currency ?? 'EUR';

            if ($accommodation->location_id) {
                $this->location_mode = 'existing';
                $this->location_id = $accommodation->location_id;

                $loc = $accommodation->location;
                if ($loc) {
                    $this->address = $loc->address ?? '';
                    $this->city = $loc->city ?? '';
                    $this->postal_code = $loc->postal_code ?? '';
                    $this->country = $loc->country instanceof \App\Enums\EuropeanCountry ? $loc->country->value : ($loc->country ?? '');
                    $this->latitude = $loc->latitude ? (float) $loc->latitude : null;
                    $this->longitude = $loc->longitude ? (float) $loc->longitude : null;
                    $this->location_name = $loc->name ?? '';
                }
            } else {
                $this->location_mode = 'new';
                $this->address = $accommodation->address ?? '';
                $this->city = $accommodation->city ?? '';
                $this->postal_code = $accommodation->postal_code ?? '';
                $this->country = $accommodation->country?->value ?? '';
                $this->latitude = $accommodation->latitude ? (float) $accommodation->latitude : null;
                $this->longitude = $accommodation->longitude ? (float) $accommodation->longitude : null;
            }
        }
    }

    public function setLocationMode(string $mode): void
    {
        $this->location_mode = $mode;
    }

    public function selectExistingLocation(int $id): void
    {
        $this->location_id = $id;
        $loc = Location::find($id);

        if ($loc) {
            $this->location_name = $loc->name;
            $this->address = $loc->address ?? '';
            $this->city = $loc->city ?? '';
            $this->postal_code = $loc->postal_code ?? '';
            $this->country = $loc->country instanceof \App\Enums\EuropeanCountry ? $loc->country->value : ($loc->country ?? '');
            $this->latitude = $loc->latitude ? (float) $loc->latitude : null;
            $this->longitude = $loc->longitude ? (float) $loc->longitude : null;
        }
    }

    public function search(): void
    {
        $query = trim($this->searchQuery);

        if (strlen($query) < 3) {
            $this->searchError = 'Wpisz co najmniej 3 znaki';
            $this->showResults = false;
            $this->searchResults = [];

            return;
        }

        $this->searchError = null;
        $this->isSearching = true;
        $this->showResults = false;
        $this->searchResults = [];

        try {
            $this->searchResults = $this->geocodingService->search($query, 10);
            $this->showResults = true;

            if (empty($this->searchResults)) {
                $this->searchError = 'Brak wyników dla: "'.$query.'"';
            }
        } catch (\Exception $e) {
            $this->searchError = 'Błąd wyszukiwania: '.$e->getMessage();
        } finally {
            $this->isSearching = false;
        }
    }

    public function selectGeoResult(int $index): void
    {
        if (! isset($this->searchResults[$index])) {
            return;
        }

        $result = $this->searchResults[$index];

        $this->address = $result['address'] ?? '';
        $this->city = $result['city'] ?? '';
        $this->postal_code = $result['postal_code'] ?? '';
        $this->latitude = isset($result['latitude']) ? (float) $result['latitude'] : null;
        $this->longitude = isset($result['longitude']) ? (float) $result['longitude'] : null;

        if (! empty($result['country'])) {
            $this->country = $this->mapCountryNameToCode($result['country']);
        }

        $this->searchQuery = $result['label'] ?? '';
        $this->showResults = false;
        $this->searchResults = [];
    }

    public function closeResults(): void
    {
        $this->showResults = false;
    }

    public function getFormattedLatitudeProperty(): string
    {
        return $this->latitude ? number_format((float) $this->latitude, 8) : '';
    }

    public function getFormattedLongitudeProperty(): string
    {
        return $this->longitude ? number_format((float) $this->longitude, 8) : '';
    }

    public function render()
    {
        $locations = Location::orderBy('name')->get();

        return view('livewire.accommodation-form', [
            'locations' => $locations,
        ]);
    }

    private function mapCountryNameToCode(string $name): string
    {
        $map = [
            'Poland' => 'PL', 'Polska' => 'PL',
            'Germany' => 'DE', 'Niemcy' => 'DE',
            'Czech Republic' => 'CZ', 'Czechy' => 'CZ',
            'Slovakia' => 'SK', 'Słowacja' => 'SK',
            'Ukraine' => 'UA', 'Ukraina' => 'UA',
            'Belarus' => 'BY', 'Białoruś' => 'BY',
            'Lithuania' => 'LT', 'Litwa' => 'LT',
            'France' => 'FR', 'Francja' => 'FR',
            'Italy' => 'IT', 'Włochy' => 'IT',
            'Spain' => 'ES', 'Hiszpania' => 'ES',
            'Netherlands' => 'NL', 'Holandia' => 'NL',
            'Belgium' => 'BE', 'Belgia' => 'BE',
            'Austria' => 'AT',
            'Switzerland' => 'CH', 'Szwajcaria' => 'CH',
            'United Kingdom' => 'GB', 'Wielka Brytania' => 'GB',
            'Denmark' => 'DK', 'Dania' => 'DK',
            'Sweden' => 'SE', 'Szwecja' => 'SE',
            'Norway' => 'NO', 'Norwegia' => 'NO',
            'Finland' => 'FI', 'Finlandia' => 'FI',
        ];

        return $map[$name] ?? '';
    }
}
