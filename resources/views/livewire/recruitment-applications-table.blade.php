<div>
    <div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
        <button type="button"
                wire:click="$set('status', '')"
                class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-secondary' }}">
            Wszystkie
            <span class="badge bg-secondary ms-1">{{ $counts->sum() }}</span>
        </button>

        @foreach([
            'pending'   => ['label' => 'Oczekuje',     'variant' => 'warning'],
            'reviewing' => ['label' => 'Weryfikacja',  'variant' => 'info'],
            'accepted'  => ['label' => 'Zaakceptowane','variant' => 'success'],
            'rejected'  => ['label' => 'Odrzucone',    'variant' => 'danger'],
            'converted' => ['label' => 'Zatrudnieni',  'variant' => 'secondary'],
        ] as $key => $cfg)
            <button type="button"
                    wire:click="$set('status', '{{ $key }}')"
                    class="btn btn-sm {{ $status === $key ? 'btn-'.$cfg['variant'] : 'btn-outline-secondary' }}">
                {{ $cfg['label'] }}
                @if($counts->has($key))
                    <span class="badge bg-secondary ms-1">{{ $counts[$key] }}</span>
                @endif
            </button>
        @endforeach

        <div class="ms-auto">
            <a href="/rekrutacja" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i> Otwórz formularz
            </a>
        </div>
    </div>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif

    <x-ui.card class="mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h3 class="fs-6 fw-semibold mb-1">Filtry i sortowanie</h3>
                <p class="small text-muted mb-0">
                    Znaleziono: <span class="fw-semibold">{{ $applications->total() }}</span> kandydatur
                </p>
            </div>
            @if($firstName || $lastName || $phone)
                <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small" for="filter_first_name">Imię</label>
                <input type="text"
                       id="filter_first_name"
                       wire:model.live.debounce.300ms="firstName"
                       class="form-control"
                       placeholder="Szukaj po imieniu…">
            </div>
            <div class="col-md-4">
                <label class="form-label small" for="filter_last_name">Nazwisko</label>
                <input type="text"
                       id="filter_last_name"
                       wire:model.live.debounce.300ms="lastName"
                       class="form-control"
                       placeholder="Szukaj po nazwisku…">
            </div>
            <div class="col-md-4">
                <label class="form-label small" for="filter_phone">Telefon</label>
                <input type="text"
                       id="filter_phone"
                       wire:model.live.debounce.300ms="phone"
                       class="form-control"
                       placeholder="Szukaj po telefonie…">
            </div>
        </div>
    </x-ui.card>

    @if($applications->isEmpty())
        <x-ui.empty-state
            icon="person-lines-fill"
            :message="$status || $firstName || $lastName || $phone ? 'Brak kandydatur spełniających kryteria.' : 'Nie przesłano jeszcze żadnych zgłoszeń rekrutacyjnych.'"
        />
    @else
        <x-ui.card>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>
                                <button type="button" wire:click="sortBy('last_name')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Kandydat
                                    @if($sortField === 'last_name')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('desired_role')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Stanowisko
                                    @if($sortField === 'desired_role')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('phone')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Telefon
                                    @if($sortField === 'phone')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('status')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Status
                                    @if($sortField === 'status')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th>
                                <button type="button" wire:click="sortBy('created_at')" class="btn btn-link p-0 text-start text-decoration-none fw-semibold">
                                    Data zgłoszenia
                                    @if($sortField === 'created_at')
                                        <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </button>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $app)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($app->photo_url)
                                            <img src="{{ $app->photo_url }}" alt=""
                                                 class="rounded-circle"
                                                 style="width:36px;height:36px;object-fit:cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white fw-semibold"
                                                 style="width:36px;height:36px;font-size:0.8rem;">
                                                {{ mb_strtoupper(mb_substr($app->first_name, 0, 1).mb_substr($app->last_name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $app->full_name }}</div>
                                            <small class="text-muted">{{ $app->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $app->desired_role ?? '—' }}</td>
                                <td>{{ $app->phone ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $app->status_variant }}">
                                        {{ $app->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $app->created_at->format('d.m.Y H:i') }}</div>
                                    <small class="text-muted">{{ $app->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('recruitment-applications.show', $app) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye me-1"></i> Szczegóły
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($applications->hasPages())
                <div class="p-3 border-top">
                    {{ $applications->links() }}
                </div>
            @endif
        </x-ui.card>
    @endif
</div>
