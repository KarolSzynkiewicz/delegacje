<?php

namespace App\Http\Controllers;

use App\Enums\LocationPurposeType;
use App\Http\Controllers\Concerns\HandlesImageUpload;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use App\Models\Accommodation;
use App\Models\AccommodationLease;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        $location = $this->resolveLocation($validated);
        $accommodation = Accommodation::create($this->buildAccommodationData($validated, $location));

        if (($validated['type'] ?? '') === 'wynajmowany') {
            $accommodation->leases()->create([
                'type' => 'wynajmowany',
                'start_date' => $validated['lease_start_date'] ?? null,
                'end_date' => $validated['lease_end_date'] ?? null,
            ]);
        }

        if ($location) {
            $this->ensureQuarterPurposeForAccommodation($location);
        }

        return redirect()->route('accommodations.index')
            ->with('success', 'Akomodacja została dodana.');
    }

    public function show(Accommodation $accommodation): View
    {
        $accommodation->load(['activeLease', 'leases', 'location']);

        $assignments = $accommodation->assignments()
            ->with(['employee'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('accommodations.show', compact('accommodation', 'assignments'));
    }

    public function edit(Accommodation $accommodation): View
    {
        $accommodation->load('activeLease');

        return view('accommodations.edit', compact('accommodation'));
    }

    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): RedirectResponse
    {
        $validated = $request->validated();
        $validated = $this->processImageUpload($validated, $request, 'accommodations', $accommodation->image_path);

        $location = $this->resolveLocation($validated);
        $accommodation->update($this->buildAccommodationData($validated, $location));

        $activeLease = $accommodation->activeLease()->first();

        if (($validated['type'] ?? '') === 'wynajmowany') {
            $leaseData = [
                'type' => 'wynajmowany',
                'start_date' => $validated['lease_start_date'] ?? null,
                'end_date' => $validated['lease_end_date'] ?? null,
            ];

            if ($activeLease) {
                $activeLease->update($leaseData);
            } else {
                $accommodation->leases()->create($leaseData);
            }
        } elseif ($activeLease) {
            // Zmiana na "własny" – zamknij aktywny najem
            $activeLease->update(['end_date' => now()->toDateString()]);
        }

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
     * Dodaj nowy okres najmu do istniejącej akomodacji.
     */
    public function storeLease(Request $request, Accommodation $accommodation): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Zamknij aktualnie aktywny najem jeśli się nakłada
        $activeLease = $accommodation->activeLease()->first();
        if ($activeLease && (is_null($activeLease->end_date) || $activeLease->end_date->gt(now()))) {
            $activeLease->update(['end_date' => now()->toDateString()]);
        }

        $accommodation->leases()->create([
            'type' => 'wynajmowany',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('accommodations.show', $accommodation)
            ->with('success', 'Nowy okres najmu został dodany.');
    }

    public function updateLease(Request $request, Accommodation $accommodation, AccommodationLease $lease): RedirectResponse
    {
        $this->assertLeaseBelongsToAccommodation($accommodation, $lease);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lease->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('accommodations.show', $accommodation)
            ->with('success', 'Okres najmu został zaktualizowany.');
    }

    public function destroyLease(Accommodation $accommodation, AccommodationLease $lease): RedirectResponse
    {
        $this->assertLeaseBelongsToAccommodation($accommodation, $lease);

        $lease->delete();

        return redirect()->route('accommodations.show', $accommodation)
            ->with('success', 'Okres najmu został usunięty.');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function assertLeaseBelongsToAccommodation(Accommodation $accommodation, AccommodationLease $lease): void
    {
        abort_if((int) $lease->accommodation_id !== (int) $accommodation->id, 404);
    }

    private function resolveLocation(array $data): ?Location
    {
        if (! empty($data['location_id'])) {
            return Location::find((int) $data['location_id']);
        }

        // Jeśli brak nazwy lokalizacji — użyj nazwy mieszkania jako domyślnej
        if (empty($data['location_name'])) {
            $data['location_name'] = $data['name'] ?? null;
        }

        if (! empty($data['location_name'])) {
            return Location::firstOrCreate(
                [
                    'name' => $data['location_name'],
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                ],
                [
                    'postal_code' => $data['postal_code'] ?? null,
                    'country' => $data['country'] ?? null,
                    'latitude' => ! empty($data['latitude']) ? (float) $data['latitude'] : null,
                    'longitude' => ! empty($data['longitude']) ? (float) $data['longitude'] : null,
                    'is_base' => false,
                ]
            );
        }

        return null;
    }

    private function buildAccommodationData(array $data, ?Location $location): array
    {
        $result = [
            'location_id' => $location?->id,
            'name' => $data['name'],
            'capacity' => $data['capacity'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
        ];

        // Denormalise address fields from location (backward compat for views)
        if ($location) {
            $result['address'] = $location->address;
            $result['city'] = $location->city;
            $result['postal_code'] = $location->postal_code;
            $result['country'] = $location->country;
            $result['latitude'] = $location->latitude ? (float) $location->latitude : null;
            $result['longitude'] = $location->longitude ? (float) $location->longitude : null;
        } else {
            $result['address'] = $data['address'] ?? null;
            $result['city'] = $data['city'] ?? null;
            $result['postal_code'] = $data['postal_code'] ?? null;
            $result['country'] = $data['country'] ?? null;
            $result['latitude'] = ! empty($data['latitude']) ? (float) $data['latitude'] : null;
            $result['longitude'] = ! empty($data['longitude']) ? (float) $data['longitude'] : null;
        }

        return $result;
    }

    private function ensureQuarterPurposeForAccommodation(Location $location): void
    {
        $location->addPurposes([LocationPurposeType::QUARTER]);
    }
}
