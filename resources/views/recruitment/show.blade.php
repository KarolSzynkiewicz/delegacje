<x-app-layout>
    <x-slot name="header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('recruitment-applications.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h2 class="fw-semibold fs-4 mb-0">
                Kandydatura: {{ $application->full_name }}
            </h2>
            <span class="badge bg-{{ $application->status_variant }} fs-6">
                {{ $application->status_label }}
            </span>
        </div>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="row g-4">

        {{-- Lewa kolumna: dane kandydata --}}
        <div class="col-lg-7">

            <x-ui.card label="Dane kandydata" class="mb-4">
                <div class="d-flex gap-4 align-items-start">

                    @if($application->photo_url)
                        <img src="{{ $application->photo_url }}"
                             alt="Zdjęcie kandydata"
                             class="rounded"
                             style="width:100px;height:100px;object-fit:cover;flex-shrink:0;">
                    @else
                        <div class="rounded bg-secondary d-flex align-items-center justify-content-center text-white fw-bold"
                             style="width:100px;height:100px;flex-shrink:0;font-size:2rem;">
                            {{ mb_strtoupper(mb_substr($application->first_name, 0, 1).mb_substr($application->last_name, 0, 1)) }}
                        </div>
                    @endif

                    <div class="flex-grow-1">
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Imię i nazwisko">
                                {{ $application->full_name }}
                            </x-ui.detail-item>
                            <x-ui.detail-item label="E-mail">
                                <a href="mailto:{{ $application->email }}">{{ $application->email }}</a>
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Telefon">
                                {{ $application->phone ?? '—' }}
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Stanowisko">
                                {{ $application->desired_role ?? '—' }}
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Skąd o nas wie">
                                {{ $application->referral_source_label ?? '—' }}
                            </x-ui.detail-item>
                        </x-ui.detail-list>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card label="Daty zgłoszenia" class="mb-4">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Data zgłoszenia">
                        {{ $application->created_at->format('d.m.Y H:i') }}
                        <small class="text-muted">({{ $application->created_at->diffForHumans() }})</small>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Ostatnia aktualizacja">
                        {{ $application->updated_at->format('d.m.Y H:i') }}
                        <small class="text-muted">({{ $application->updated_at->diffForHumans() }})</small>
                    </x-ui.detail-item>
                    @if($application->consent_given_at)
                        <x-ui.detail-item label="Data wyrażenia zgód" fullWidth="true">
                            {{ $application->consent_given_at->format('d.m.Y H:i') }}
                        </x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>

            @if($application->cover_letter)
                <x-ui.card label="List motywacyjny" class="mb-4">
                    <div style="white-space:pre-wrap;line-height:1.7;">{{ $application->cover_letter }}</div>
                </x-ui.card>
            @endif

            <x-ui.card label="Zgody i oświadczenia" class="mb-4">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Zgoda RODO">
                        @if($application->consent_rodo)
                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Wyrażona</span>
                        @else
                            <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Brak</span>
                        @endif
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Zgoda na rekrutację (bieżącą i przyszłe)">
                        @if($application->consent_recruitment_processing)
                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Wyrażona</span>
                        @else
                            <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> Brak</span>
                        @endif
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Zgoda marketingowa">
                        @if($application->consent_marketing)
                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Wyrażona</span>
                        @else
                            <span class="text-muted"><i class="bi bi-dash-circle me-1"></i> Nie wyrażona</span>
                        @endif
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data wyrażenia zgód" fullWidth="true">
                        {{ $application->consent_given_at?->format('d.m.Y H:i') ?? '—' }}
                    </x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>

            @if($application->status === 'converted' && $application->employee)
                <x-ui.alert variant="success" class="mb-4">
                    <i class="bi bi-person-check-fill me-2"></i>
                    Kandydat został zatrudniony.
                    <a href="{{ route('employees.show', $application->employee) }}" class="fw-semibold">
                        Przejdź do profilu pracownika →
                    </a>
                </x-ui.alert>
            @endif

            <x-comments :commentable="$application" class="mb-4" />

        </div>

        {{-- Prawa kolumna: akcje --}}
        <div class="col-lg-5 d-flex flex-column gap-4">

            {{-- Zmiana statusu --}}
            <x-ui.card label="Zmień status">
                <form action="{{ route('recruitment-applications.update-status', $application) }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label" for="status">Status kandydatury</label>
                        <select name="status" id="status" class="form-select">
                            @foreach([
                                'pending'   => 'Oczekuje',
                                'reviewing' => 'W trakcie weryfikacji',
                                'accepted'  => 'Zaakceptowany',
                                'rejected'  => 'Odrzucony',
                                'converted' => 'Zatrudniony',
                            ] as $val => $label)
                                <option value="{{ $val }}" {{ $application->status === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="admin_notes">Notatki wewnętrzne</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3"
                                  placeholder="Notatki widoczne tylko dla administratorów…">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i> Zapisz status
                    </button>
                </form>
            </x-ui.card>

            {{-- Konwersja do pracownika --}}
            @if($application->status !== 'converted')
                <x-ui.card label="Zatrudnij kandydata">
                    <p class="text-muted small mb-3">
                        Utworzy nowego pracownika na podstawie danych kandydata.
                        Wybierz przypisywane role.
                    </p>

                    <form action="{{ route('recruitment-applications.convert', $application) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">
                                Role pracownika <span class="text-danger">*</span>
                            </label>
                            <div class="border rounded p-3 @error('roles') border-danger @enderror"
                                 style="max-height:200px;overflow-y:auto;">
                                @forelse($roles as $role)
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            id="role_{{ $role->id }}"
                                            name="roles[]"
                                            value="{{ $role->id }}"
                                            class="form-check-input"
                                            {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->name }}
                                            @if($role->description ?? false)
                                                <small class="text-muted d-block">{{ $role->description }}</small>
                                            @endif
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-muted mb-0 small">
                                        Brak ról w systemie.
                                        <a href="{{ route('roles.create') }}">Utwórz rolę</a>
                                    </p>
                                @endforelse
                            </div>
                            @error('roles')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            class="btn btn-success w-100"
                            onclick="return confirm('Czy na pewno chcesz zatrudnić tego kandydata?')"
                        >
                            <i class="bi bi-person-plus me-1"></i> Zatrudnij kandydata
                        </button>
                    </form>
                </x-ui.card>
            @endif

        </div>

    </div>

</x-app-layout>
