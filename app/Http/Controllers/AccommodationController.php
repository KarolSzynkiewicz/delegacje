<?php

namespace App\Http\Controllers;

use App\Enums\LocationPurposeType;
use App\Models\Accommodation;
use App\Models\Location;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccommodationController extends Controller
{
    use HandlesImageUpload;

    public function index(): View
    {
        return view('accommodations.index');
    }

    public function create(): View
    {
        return view('accommodations.create');
    }

    public function store(StoreAccommodationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated = $this->processImageUpload($validated, $request, 'accommodations');

        if (($validated['type'] ?? '') === 'własny') {
            $validated['lease_start_date'] = null;
            $validated['lease_end_date']   = null;
        }

        $location = $this->resolveLocation($validated);

        $accommodationData = $this->buildAccommodationData($validated, $location);

        $accommodation = Accommodation::create($accommodationData);

        if ($location) {
            $this->ensureQuarterPurposeForAccommodation($location);
        }

        return redirect()->route('accommodations.index')
            ->with('success', 'Akomodacja została dodana.');
    }

    public function show(Accommodation $accommodation): View
    {
        $assignments = $accommodation->assignments()
            ->with(['employee'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('accommodations.show', compact('accommodation', 'assignments'));
    }

    public function edit(Accommodation $accommodation): View
    {
        return view('accommodations.edit', compact('accommodation'));
    }

    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): RedirectResponse
    {
        $validated = $request->validated();
        $validated = $this->processImageUpload($validated, $request, 'accommodations', $accommodation->image_path);

        if (($validated['type'] ?? '') === 'własny') {
            $validated['lease_start_date'] = null;
            $validated['lease_end_date']   = null;
        }

        $location = $this->resolveLocation($validated);

        $accommodationData = $this->buildAccommodationData($validated, $location);

        $accommodation->update($accommodationData);

        if ($location) {
            $this->ensureQuarterPurposeForAccommodation($location);
        }

        return redirect()->route('accommodations.show', $accommodation)
            ->with('success', 'Akomodacja została zaktualizowana.');
    }

    public function destroy(Accommodation $accommodation): RedirectResponse
    {
        $accommodation->delete();

        return redirect()->route('accommodations.index')
            ->with('success', 'Akomodacja została usunięta.');
    }

    /**
     * Find an existing Location or create a new one based on the request data.
     */
    private function resolveLocation(array $data): ?Location
    {
        if (!empty($data['location_id'])) {
            return Location::find((int) $data['location_id']);
        }

        if (!empty($data['location_name'])) {
            $location = Location::firstOrCreate(
                [
                    'name'    => $data['location_name'],
                    'address' => $data['address'] ?? null,
                    'city'    => $data['city']    ?? null,
                ],
                [
                    'postal_code' => $data['postal_code'] ?? null,
                    'country'     => $data['country']     ?? null,
                    'latitude'    => !empty($data['latitude'])  ? (float) $data['latitude']  : null,
                    'longitude'   => !empty($data['longitude']) ? (float) $data['longitude'] : null,
                    'is_base'     => false,
                ]
            );

            return $location;
        }

        return null;
    }

    /**
     * Build the fillable array for Accommodation from request data + resolved location.
     */
    private function buildAccommodationData(array $data, ?Location $location): array
    {
        $result = [
            'location_id'      => $location?->id,
            'name'             => $data['name'],
            'capacity'         => $data['capacity'],
            'type'             => $data['type'],
            'description'      => $data['description'] ?? null,
            'image_path'       => $data['image_path']  ?? null,
            'lease_start_date' => $data['lease_start_date'] ?? null,
            'lease_end_date'   => $data['lease_end_date']   ?? null,
        ];

        // Denormalise address fields from location (keeps backward compat for existing views)
        if ($location) {
            $result['address']     = $location->address;
            $result['city']        = $location->city;
            $result['postal_code'] = $location->postal_code;
            $result['country']     = $location->country;
            $result['latitude']    = $location->latitude  ? (float) $location->latitude  : null;
            $result['longitude']   = $location->longitude ? (float) $location->longitude : null;
        } else {
            $result['address']     = $data['address']     ?? null;
            $result['city']        = $data['city']        ?? null;
            $result['postal_code'] = $data['postal_code'] ?? null;
            $result['country']     = $data['country']     ?? null;
            $result['latitude']    = !empty($data['latitude'])  ? (float) $data['latitude']  : null;
            $result['longitude']   = !empty($data['longitude']) ? (float) $data['longitude'] : null;
        }

        return $result;
    }

    /**
     * Akomodacja = zawsze kwatera: dopisz cel „Kwatera” do lokalizacji (idempotentnie).
     */
    private function ensureQuarterPurposeForAccommodation(Location $location): void
    {
        $location->addPurposes([LocationPurposeType::QUARTER]);
    }
}
