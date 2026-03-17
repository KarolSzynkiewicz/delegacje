<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected string $nominatimUrl = 'https://nominatim.openstreetmap.org';
    protected string $userAgent = 'DelegacjeApp/1.0 (contact@delegacje.local)';

    /**
     * Geocode an address to coordinates using Nominatim (OpenStreetMap).
     *
     * @param string $address Full address string
     * @return array|null ['latitude' => float, 'longitude' => float] or null on failure
     */
    public function geocode(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])->get("{$this->nominatimUrl}/search", [
                'q' => $address,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                Log::error('Geocoding failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'address' => $address,
                ]);
                return null;
            }

            $data = $response->json();

            if (empty($data)) {
                Log::warning('No geocoding results found', ['address' => $address]);
                return null;
            }

            $result = $data[0];

            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
            ];
        } catch (\Exception $e) {
            Log::error('Geocoding exception', [
                'message' => $e->getMessage(),
                'address' => $address,
            ]);
            return null;
        }
    }

    /**
     * Search for locations (autocomplete) using Nominatim.
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array Array of location results
     */
    public function search(string $query, int $limit = 8): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept' => 'application/json',
            ])->get("{$this->nominatimUrl}/search", [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => $limit,
                'accept-language' => 'pl,en',
            ]);

            if (!$response->successful()) {
                Log::error('Geocoding search failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return [];
            }

            $data = $response->json();

            if (empty($data)) {
                return [];
            }

            $results = [];
            foreach ($data as $item) {
                $addressDetails = $item['address'] ?? [];

                $street = trim(($addressDetails['road'] ?? '') . ' ' . ($addressDetails['house_number'] ?? ''));
                $city = $addressDetails['city']
                    ?? $addressDetails['town']
                    ?? $addressDetails['village']
                    ?? $addressDetails['municipality']
                    ?? '';
                $postalCode = $addressDetails['postcode'] ?? '';
                $country = $addressDetails['country'] ?? '';

                $results[] = [
                    'label' => $item['display_name'],
                    'address' => $street,
                    'city' => $city,
                    'postal_code' => $postalCode,
                    'country' => $country,
                    'latitude' => (float) $item['lat'],
                    'longitude' => (float) $item['lon'],
                ];
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Geocoding search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return [];
        }
    }

    /**
     * Geocode a Location model.
     *
     * @param Location $location
     * @return bool True if geocoding was successful
     */
    public function geocodeLocation(Location $location): bool
    {
        if ($location->hasCoordinates()) {
            return true; // Already has coordinates
        }

        $address = $location->getFullAddress();
        if (empty($address)) {
            Log::warning('Location has no address to geocode', ['location_id' => $location->id]);
            return false;
        }

        $coordinates = $this->geocode($address);
        if (!$coordinates) {
            return false;
        }

        $location->update([
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ]);

        return true;
    }

    /**
     * Batch geocode multiple locations.
     *
     * @param \Illuminate\Support\Collection|array $locations
     * @return int Number of successfully geocoded locations
     */
    public function geocodeLocations($locations): int
    {
        $count = 0;

        foreach ($locations as $location) {
            if ($this->geocodeLocation($location)) {
                $count++;
            }

            // Rate limiting: Nominatim requires max 1 request per second
            sleep(1);
        }

        return $count;
    }
}
