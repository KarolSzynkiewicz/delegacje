<?php

namespace App\Livewire;

use App\Models\Location;
use App\Services\GeocodingService;
use Livewire\Component;

class WorkshopSearch extends Component
{
    // Mode: 'existing' | 'new'
    public string $mode = 'existing';

    // Existing location selection
    public ?int $location_id = null;

    // New workshop fields (geo search)
    public string $searchQuery = '';
    public array  $searchResults = [];
    public bool   $isSearching = false;
    public bool   $showResults = false;
    public ?string $searchError = null;

    public string $workshop_name        = '';
    public string $workshop_address     = '';
    public string $workshop_city        = '';
    public string $workshop_postal_code = '';
    public string $workshop_country     = '';
    public ?float $workshop_lat         = null;
    public ?float $workshop_lng         = null;

    protected GeocodingService $geocodingService;

    public function boot(GeocodingService $geocodingService): void
    {
        $this->geocodingService = $geocodingService;
    }

    public function mount(?int $locationId = null): void
    {
        if ($locationId) {
            $this->location_id = $locationId;
            $this->mode = 'existing';
        }
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
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
                $this->searchError = 'Brak wyników dla: "' . $query . '"';
            }
        } catch (\Exception $e) {
            $this->searchError = 'Błąd wyszukiwania: ' . $e->getMessage();
        } finally {
            $this->isSearching = false;
        }
    }

    public function selectResult(int $index): void
    {
        if (!isset($this->searchResults[$index])) {
            return;
        }

        $result = $this->searchResults[$index];

        $this->workshop_address     = $result['address'] ?? '';
        $this->workshop_city        = $result['city'] ?? '';
        $this->workshop_postal_code = $result['postal_code'] ?? '';
        $this->workshop_lat         = isset($result['latitude'])  ? (float) $result['latitude']  : null;
        $this->workshop_lng         = isset($result['longitude']) ? (float) $result['longitude'] : null;

        if (!empty($result['country'])) {
            $this->workshop_country = $this->mapCountryNameToCode($result['country']);
        }

        if (empty($this->workshop_name)) {
            $this->workshop_name = $result['label'] ?? '';
        }

        $this->searchQuery  = $result['label'] ?? '';
        $this->showResults  = false;
        $this->searchResults = [];
    }

    public function closeResults(): void
    {
        $this->showResults = false;
    }

    private function mapCountryNameToCode(string $name): string
    {
        $map = [
            'Poland' => 'PL', 'Polska' => 'PL',
            'Germany' => 'DE', 'Niemcy' => 'DE',
            'Czech Republic' => 'CZ', 'Czechy' => 'CZ',
            'Slovakia' => 'SK', 'Słowacja' => 'SK',
            'Ukraine' => 'UA', 'Ukraina' => 'UA',
            'Lithuania' => 'LT', 'Litwa' => 'LT',
            'France' => 'FR', 'Francja' => 'FR',
            'Italy' => 'IT', 'Włochy' => 'IT',
            'Spain' => 'ES', 'Hiszpania' => 'ES',
            'Netherlands' => 'NL', 'Holandia' => 'NL',
            'Belgium' => 'BE', 'Belgia' => 'BE',
            'Austria' => 'AT',
            'Switzerland' => 'CH', 'Szwajcaria' => 'CH',
            'United Kingdom' => 'GB', 'Wielka Brytania' => 'GB',
        ];

        return $map[$name] ?? '';
    }

    public function render()
    {
        $locations = Location::orderBy('name')->get();

        return view('livewire.workshop-search', compact('locations'));
    }
}
