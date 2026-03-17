<?php

namespace App\Livewire;

use App\Models\Location;
use App\Services\GeocodingService;
use Livewire\Component;

class LocationForm extends Component
{
    // Form fields
    public $name = '';
    public $address = '';
    public $city = '';
    public $postal_code = '';
    public $country = '';
    public $contact_person = '';
    public $phone = '';
    public $email = '';
    public $description = '';
    public $is_base = false;
    public $latitude = null;
    public $longitude = null;
    
    // Search
    public $searchQuery = '';
    public $searchResults = [];
    public $isSearching = false;
    public $showResults = false;
    
    protected $geocodingService;
    
    public function boot(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }
    
    public function mount(?Location $location = null)
    {
        if ($location) {
            $this->name = $location->name;
            $this->address = $location->address;
            $this->city = $location->city ?? '';
            $this->postal_code = $location->postal_code ?? '';
            $this->country = $location->country?->value ?? '';
            $this->contact_person = $location->contact_person ?? '';
            $this->phone = $location->phone ?? '';
            $this->email = $location->email ?? '';
            $this->description = $location->description ?? '';
            $this->is_base = $location->is_base ?? false;
            $this->latitude = $location->latitude;
            $this->longitude = $location->longitude;
        }
    }
    
    public $searchError = null;
    
    // Removed automatic search on input change - now using manual search button
    
    public function search()
    {
        $query = trim($this->searchQuery);
        
        if (empty($query)) {
            $this->searchError = 'Wpisz co najmniej 1 znak';
            $this->showResults = false;
            $this->searchResults = [];
            $this->isSearching = false;
            return;
        }
        
        if (strlen($query) < 3) {
            $this->searchError = 'Wpisz co najmniej 3 znaki';
            $this->showResults = false;
            $this->searchResults = [];
            $this->isSearching = false;
            return;
        }
        
        $this->searchError = null;
        $this->isSearching = true;
        $this->showResults = false;
        $this->searchResults = [];
        
        try {
            \Illuminate\Support\Facades\Log::info('Starting location search', ['query' => $query]);
            
            if (!$this->geocodingService) {
                throw new \Exception('GeocodingService not initialized');
            }
            
            $results = $this->geocodingService->search($query, 10);
            
            \Illuminate\Support\Facades\Log::info('Location search completed', [
                'query' => $query,
                'results_count' => count($results),
            ]);
            
            $this->searchResults = $results;
            $this->showResults = true;
            
            if (empty($results)) {
                $this->searchError = 'Brak wyników dla zapytania: "' . $query . '"';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Location search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->searchError = 'Błąd wyszukiwania: ' . $e->getMessage();
            $this->searchResults = [];
            $this->showResults = false;
        } finally {
            $this->isSearching = false;
        }
    }
    
    public function selectLocation($index)
    {
        if (!isset($this->searchResults[$index])) {
            return;
        }
        
        $result = $this->searchResults[$index];
        
        $this->address = $result['address'] ?? '';
        $this->city = $result['city'] ?? '';
        $this->postal_code = $result['postal_code'] ?? '';
        $this->latitude = $result['latitude'] ?? null;
        $this->longitude = $result['longitude'] ?? null;
        
        // Set country if available
        if (!empty($result['country'])) {
            $countryValue = $this->mapCountryNameToValue($result['country']);
            if ($countryValue) {
                $this->country = $countryValue;
            }
        }
        
        $this->searchQuery = $result['label'] ?? '';
        $this->showResults = false;
        $this->searchResults = [];
    }
    
    protected function mapCountryNameToValue(string $countryName): ?string
    {
        $countryMap = [
            'Poland' => 'PL',
            'Polska' => 'PL',
            'Germany' => 'DE',
            'Niemcy' => 'DE',
            'Czech Republic' => 'CZ',
            'Czechy' => 'CZ',
            'Slovakia' => 'SK',
            'Słowacja' => 'SK',
            'Ukraine' => 'UA',
            'Ukraina' => 'UA',
            'Belarus' => 'BY',
            'Białoruś' => 'BY',
            'Lithuania' => 'LT',
            'Litwa' => 'LT',
            'France' => 'FR',
            'Francja' => 'FR',
            'Italy' => 'IT',
            'Włochy' => 'IT',
            'Spain' => 'ES',
            'Hiszpania' => 'ES',
            'Netherlands' => 'NL',
            'Holandia' => 'NL',
            'Belgium' => 'BE',
            'Belgia' => 'BE',
            'Austria' => 'AT',
            'Austria' => 'AT',
            'Switzerland' => 'CH',
            'Szwajcaria' => 'CH',
            'United Kingdom' => 'GB',
            'Wielka Brytania' => 'GB',
            'Denmark' => 'DK',
            'Dania' => 'DK',
            'Sweden' => 'SE',
            'Szwecja' => 'SE',
            'Norway' => 'NO',
            'Norwegia' => 'NO',
            'Finland' => 'FI',
            'Finlandia' => 'FI',
        ];
        
        return $countryMap[$countryName] ?? null;
    }
    
    public function closeResults()
    {
        $this->showResults = false;
    }
    
    public function getFormattedLatitudeProperty()
    {
        return $this->latitude ? number_format((float)$this->latitude, 8) : '';
    }
    
    public function getFormattedLongitudeProperty()
    {
        return $this->longitude ? number_format((float)$this->longitude, 8) : '';
    }
    
    public function render()
    {
        return view('livewire.location-form');
    }
}
