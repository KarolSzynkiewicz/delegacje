<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GeocodingController extends Controller
{
    public function __construct(
        protected GeocodingService $geocodingService
    ) {}

    /**
     * Search for locations using Nominatim (autocomplete).
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|min:3',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Invalid request',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $query = $request->input('query');

        try {
            $results = $this->geocodingService->search($query);

            return response()->json([
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Geocoding search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Search failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Geocode a specific address.
     */
    public function geocode(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string',
        ]);

        $address = $request->input('address');
        $coordinates = $this->geocodingService->geocode($address);

        if (!$coordinates) {
            return response()->json([
                'error' => 'Could not geocode address',
            ], 404);
        }

        return response()->json($coordinates);
    }
}
