<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoutePlanningService
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openrouteservice.api_key');
        $this->baseUrl = config('services.openrouteservice.base_url');
    }

    /**
     * Plan a route between two locations.
     *
     * @param  array  $options  Additional options (profile: 'driving-car', 'driving-hgv', etc.)
     * @return array|null Route data or null on failure
     */
    public function planRoute(Location $from, Location $to, array $options = []): ?array
    {
        if (! $from->hasCoordinates() || ! $to->hasCoordinates()) {
            Log::warning('Cannot plan route: locations missing coordinates', [
                'from_id' => $from->id,
                'to_id' => $to->id,
            ]);

            return null;
        }

        $profile = $options['profile'] ?? 'driving-car';
        $fromCoords = $from->getCoordinates();
        $toCoords = $to->getCoordinates();

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/directions/{$profile}", [
                'coordinates' => [
                    [$fromCoords[1], $fromCoords[0]], // [longitude, latitude]
                    [$toCoords[1], $toCoords[0]],
                ],
                'format' => 'json',
                'geometry' => true,
                'instructions' => true,
                'extra_info' => [
                    'steepness',
                    'waytype',
                    'surface',
                    'waycategory',
                    'tollways',
                ],
            ]);

            if (! $response->successful()) {
                Log::error('Route planning failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'from' => $from->id,
                    'to' => $to->id,
                ]);

                return null;
            }

            $data = $response->json();

            if (empty($data['routes'])) {
                Log::warning('No route found', [
                    'from' => $from->id,
                    'to' => $to->id,
                ]);

                return null;
            }

            $route = $data['routes'][0];
            $summary = $route['summary'];

            return [
                'distance' => $summary['distance'] / 1000, // Convert to km
                'duration' => $summary['duration'], // Seconds
                'geometry' => $route['geometry'], // Encoded polyline
                'segments' => $route['segments'] ?? [],
                'way_points' => $route['way_points'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Route planning exception', [
                'message' => $e->getMessage(),
                'from' => $from->id,
                'to' => $to->id,
            ]);

            return null;
        }
    }

    /**
     * Plan a route with multiple waypoints (optimized order).
     *
     * @param  mixed  $start  Start location (Location or Accommodation with hasCoordinates/getCoordinates methods)
     * @param  mixed  $end  End location (Location or Accommodation with hasCoordinates/getCoordinates methods)
     * @param  array  $waypoints  Intermediate locations (Location or Accommodation objects)
     * @param  array  $options  Additional options
     * @return array|null Route data or null on failure
     */
    public function planRouteWithWaypoints($start, $end, array $waypoints = [], array $options = []): ?array
    {
        // Check all locations have coordinates
        $allLocations = array_merge([$start, $end], $waypoints);
        foreach ($allLocations as $location) {
            if (! method_exists($location, 'hasCoordinates') || ! $location->hasCoordinates()) {
                Log::warning('Cannot plan route: location missing coordinates', [
                    'location_id' => $location->id ?? 'unknown',
                    'location_type' => get_class($location),
                ]);

                return null;
            }
        }

        $profile = $options['profile'] ?? 'driving-car';
        $optimize = $options['optimize'] ?? true;

        // Build coordinates array: [start, ...waypoints, end]
        $coordinates = [];
        $startCoords = $start->getCoordinates();
        $coordinates[] = [$startCoords[1], $startCoords[0]]; // [lng, lat]

        foreach ($waypoints as $waypoint) {
            $coords = $waypoint->getCoordinates();
            $coordinates[] = [$coords[1], $coords[0]];
        }

        $endCoords = $end->getCoordinates();
        $coordinates[] = [$endCoords[1], $endCoords[0]];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/directions/{$profile}", [
                'coordinates' => $coordinates,
                'format' => 'json',
                'geometry' => true,
                'instructions' => true,
                'extra_info' => [
                    'steepness',
                    'waytype',
                    'surface',
                    'waycategory',
                    'tollways',
                ],
            ]);

            if (! $response->successful()) {
                $errorBody = $response->body();
                $errorData = $response->json();

                Log::error('Route planning with waypoints failed', [
                    'status' => $response->status(),
                    'response' => $errorBody,
                    'coordinates_count' => count($coordinates),
                ]);

                // Try to extract error message from response
                $errorMessage = 'Błąd API';
                if (isset($errorData['error'])) {
                    $errorMessage = is_string($errorData['error']) ? $errorData['error'] : ($errorData['error']['message'] ?? 'Błąd API');
                }

                throw new \Exception("OpenRouteService API error ({$response->status()}): {$errorMessage}");
            }

            $data = $response->json();

            if (empty($data['routes'])) {
                Log::warning('Route planning returned no routes', [
                    'coordinates_count' => count($coordinates),
                    'response_keys' => array_keys($data ?? []),
                ]);
                throw new \Exception('API nie zwróciło żadnej trasy dla podanych współrzędnych.');
            }

            $route = $data['routes'][0];
            $summary = $route['summary'];

            return [
                'distance' => $summary['distance'] / 1000, // Convert to km
                'duration' => $summary['duration'], // Seconds
                'geometry' => $route['geometry'], // Encoded polyline
                'segments' => $route['segments'] ?? [],
                'way_points' => $route['way_points'] ?? [],
                'waypoint_order' => $data['waypoints'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Route planning with waypoints exception', [
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate distance and duration between two locations.
     *
     * @return array|null ['distance' => float (km), 'duration' => int (seconds)]
     */
    public function calculateDistance(Location $from, Location $to, array $options = []): ?array
    {
        $route = $this->planRoute($from, $to, $options);

        if (! $route) {
            return null;
        }

        return [
            'distance' => $route['distance'],
            'duration' => $route['duration'],
        ];
    }
}
