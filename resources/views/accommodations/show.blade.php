<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Akomodacja: {{ $accommodation->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.edit', $accommodation) }}"
                    routeName="accommodations.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Informacje podstawowe">
                @if($accommodation->image_path)
                    <div class="mb-4 text-center">
                        <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="img-fluid rounded">
                    </div>
                @endif

                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa">{{ $accommodation->name }}</x-ui.detail-item>

                    {{-- Lokalizacja --}}
                    @if($accommodation->location)
                    <x-ui.detail-item label="Lokalizacja">
                        <a href="{{ route('locations.show', $accommodation->location) }}" class="text-decoration-none fw-medium">
                            <i class="bi bi-geo-alt me-1"></i>{{ $accommodation->location->name }}
                        </a>
                        @if($accommodation->location->address)
                            <div class="small text-muted mt-1">
                                {{ $accommodation->location->address }}
                                @if($accommodation->location->city), {{ $accommodation->location->city }}@endif
                                @if($accommodation->location->postal_code) {{ $accommodation->location->postal_code }}@endif
                                @if($accommodation->location->country) · {{ $accommodation->location->country->labelWithFlag() }}@endif
                            </div>
                        @endif
                        @if($accommodation->location->hasCoordinates())
                            <div class="mt-1">
                                <a
                                    href="https://www.openstreetmap.org/?mlat={{ floatval($accommodation->location->latitude) }}&mlon={{ floatval($accommodation->location->longitude) }}&zoom=15"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    <i class="bi bi-map"></i> Mapa
                                </a>
                            </div>
                        @endif
                    </x-ui.detail-item>
                    @elseif($accommodation->address)
                    <x-ui.detail-item label="Adres">
                        {{ $accommodation->address }}
                        @if($accommodation->city), {{ $accommodation->city }}@endif
                        @if($accommodation->country) · {{ $accommodation->country->labelWithFlag() }}@endif
                    </x-ui.detail-item>
                    @endif

                    <x-ui.detail-item label="Pojemność">{{ $accommodation->capacity }} osób</x-ui.detail-item>
                    <x-ui.detail-item label="Status">
                        @if($accommodation->is_rented)
                            <x-ui.badge variant="info">Wynajmowany</x-ui.badge>
                        @else
                            <x-ui.badge variant="success">Własny</x-ui.badge>
                        @endif
                    </x-ui.detail-item>
                    @if($accommodation->activeLease)
                        <x-ui.detail-item label="Aktywny najem">
                            {{ $accommodation->activeLease->period_label }}
                        </x-ui.detail-item>
                    @endif
                    @if($accommodation->description)
                    <x-ui.detail-item label="Opis" fullWidth>{{ $accommodation->description }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>

            {{-- ── Historia najmu ── --}}
            <x-ui.card label="Historia najmu" class="mt-4">
                @php
                    $openLeaseEditId = old('lease_edit') !== null && old('lease_edit') !== '' ? (int) old('lease_edit') : null;
                @endphp
                @if($accommodation->leases->isNotEmpty())
                    <div class="table-responsive mb-3" x-data="{ leaseEditId: @json($openLeaseEditId) }">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Od</th>
                                    <th>Do</th>
                                    <th>Czynsz</th>
                                    <th>Status</th>
                                    <th>Uwagi</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($accommodation->leases as $lease)
                                    <tr>
                                        <td>
                                            @if($lease->type === 'wynajmowany')
                                                <x-ui.badge variant="info">Wynajem</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="success">Własny</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>{{ $lease->start_date?->format('d.m.Y') ?? '—' }}</td>
                                        <td>{{ $lease->end_date?->format('d.m.Y') ?? 'bezterminowo' }}</td>
                                        <td class="text-nowrap">
                                            @if($lease->monthly_rent !== null)
                                                <strong>{{ number_format((float) $lease->monthly_rent, 2, ',', ' ') }}</strong>
                                                <small class="text-muted">{{ $lease->currency ?? '' }} / mc</small>
                                            @else
                                                <small class="text-muted">—</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($lease->isActive())
                                                <x-ui.badge variant="success">Aktywny</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="secondary">Zakończony</x-ui.badge>
                                            @endif
                                        </td>
                                        <td><small class="text-muted">{{ $lease->notes ?? '—' }}</small></td>
                                        <td class="text-end text-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                @click="leaseEditId = leaseEditId === {{ $lease->id }} ? null : {{ $lease->id }}"
                                            >
                                                <span x-show="leaseEditId !== {{ $lease->id }}">Edytuj</span>
                                                <span x-show="leaseEditId === {{ $lease->id }}" x-cloak>Zwiń</span>
                                            </button>
                                            <form
                                                action="{{ route('accommodations.leases.destroy', [$accommodation, $lease]) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Usunąć ten okres najmu? Tej operacji nie można cofnąć.');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Usuń</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr x-show="leaseEditId === {{ $lease->id }}" x-cloak>
                                        <td colspan="7" class="bg-body-secondary bg-opacity-10 border-bottom">
                                            <form
                                                method="POST"
                                                action="{{ route('accommodations.leases.update', [$accommodation, $lease]) }}"
                                                class="p-2"
                                            >
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="lease_edit" value="{{ $lease->id }}">
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-3">
                                                        <label class="form-label small mb-1">Od <span class="text-danger">*</span></label>
                                                        <input
                                                            type="date"
                                                            name="start_date"
                                                            class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                                                            value="{{ old('lease_edit') == $lease->id ? old('start_date', $lease->start_date?->format('Y-m-d')) : $lease->start_date?->format('Y-m-d') }}"
                                                            required
                                                        >
                                                        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small mb-1">Do</label>
                                                        <input
                                                            type="date"
                                                            name="end_date"
                                                            class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                                                            value="{{ old('lease_edit') == $lease->id ? old('end_date', $lease->end_date?->format('Y-m-d')) : $lease->end_date?->format('Y-m-d') }}"
                                                        >
                                                        <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small mb-1">Czynsz / mc</label>
                                                        <input
                                                            type="number"
                                                            name="monthly_rent"
                                                            step="0.01"
                                                            min="0"
                                                            class="form-control form-control-sm @error('monthly_rent') is-invalid @enderror"
                                                            value="{{ old('lease_edit') == $lease->id ? old('monthly_rent', $lease->monthly_rent) : $lease->monthly_rent }}"
                                                            placeholder="np. 1500"
                                                        >
                                                        <x-input-error :messages="$errors->get('monthly_rent')" class="mt-1" />
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small mb-1">Waluta</label>
                                                        <select name="currency" class="form-select form-select-sm">
                                                            @foreach(['EUR','PLN','USD','GBP'] as $cur)
                                                                <option value="{{ $cur }}" {{ ($lease->currency ?? 'EUR') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small mb-1">Uwagi</label>
                                                        <input
                                                            type="text"
                                                            name="notes"
                                                            class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                                            value="{{ old('lease_edit') == $lease->id ? old('notes', $lease->notes) : $lease->notes }}"
                                                            placeholder="Uwagi"
                                                        >
                                                        <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                                                    </div>
                                                </div>
                                                <div class="mt-2 d-flex gap-2 justify-content-end">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="leaseEditId = null">Anuluj</button>
                                                    <x-ui.button type="submit" variant="primary" class="btn-sm">Zapisz</x-ui.button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state icon="calendar-x" message="Brak historii najmu." class="mb-3" />
                @endif

                {{-- Formularz dodania nowego okresu najmu --}}
                <div class="border-top pt-3">
                    <h6 class="fw-semibold mb-3"><i class="bi bi-plus-circle me-1"></i> Dodaj nowy okres najmu</h6>
                    <form action="{{ route('accommodations.leases.store', $accommodation) }}" method="POST">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Od <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control form-control-sm @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Do</label>
                                <input type="date" name="end_date" class="form-control form-control-sm @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Czynsz / mc</label>
                                <input type="number" step="0.01" min="0" name="monthly_rent" class="form-control form-control-sm @error('monthly_rent') is-invalid @enderror" value="{{ old('monthly_rent') }}" placeholder="np. 1500">
                                <x-input-error :messages="$errors->get('monthly_rent')" class="mt-1" />
                            </div>
                            <div class="col-md-1">
                                <label class="form-label small mb-1">Waluta</label>
                                <select name="currency" class="form-select form-select-sm">
                                    @foreach(['EUR','PLN','USD','GBP'] as $cur)
                                        <option value="{{ $cur }}" {{ old('currency', 'EUR') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Uwagi</label>
                                <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="np. przedłużenie">
                            </div>
                            <div class="col-md-1">
                                <x-ui.button type="submit" variant="primary" class="btn-sm w-100">
                                    <i class="bi bi-plus"></i>
                                </x-ui.button>
                            </div>
                        </div>
                    </form>
                </div>
            </x-ui.card>

            <x-ui.card label="Przypisania do mieszkania" class="mt-4">
                @if($assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Okres</th>
                                    <th>Status</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                                <x-employee-cell :employee="$assignment->employee"  />
                                            </a>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $assignment->start_date->format('Y-m-d') }}
                                                @if($assignment->end_date)
                                                    - {{ $assignment->end_date->format('Y-m-d') }}
                                                @else
                                                    - ...
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                $badgeVariant = match($statusValue) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger',
                                                    'in_transit' => 'warning',
                                                    'at_base' => 'info',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                        </td>
                                        <td class="text-end">
                                            <x-ui.button variant="ghost" href="{{ route('accommodation-assignments.show', $assignment) }}">Szczegóły</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state 
                        icon="inbox"
                        message="Brak przypisań do tego mieszkania."
                    />
                @endif
            </x-ui.card>

            <div class="mt-4">
                <x-comments :commentable="$accommodation" />
            </div>
        </div>
    </div>
</x-app-layout>
