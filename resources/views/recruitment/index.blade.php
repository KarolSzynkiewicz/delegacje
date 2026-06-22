<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">Kandydatury rekrutacyjne</h2>
    </x-slot>

    <div class="mb-4 d-flex flex-wrap gap-2 align-items-center">

        {{-- Filtry statusu --}}
        <a href="{{ route('recruitment-applications.index') }}"
           class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-secondary' }}">
            Wszystkie
            <span class="badge bg-secondary ms-1">{{ $counts->sum() }}</span>
        </a>

        @foreach([
            'pending'   => ['label' => 'Oczekuje',    'variant' => 'warning'],
            'reviewing' => ['label' => 'Weryfikacja', 'variant' => 'info'],
            'accepted'  => ['label' => 'Zaakceptowane','variant' => 'success'],
            'rejected'  => ['label' => 'Odrzucone',   'variant' => 'danger'],
            'converted' => ['label' => 'Przeniesieni','variant' => 'secondary'],
        ] as $key => $cfg)
            <a href="{{ route('recruitment-applications.index', ['status' => $key]) }}"
               class="btn btn-sm {{ $status === $key ? 'btn-'.$cfg['variant'] : 'btn-outline-secondary' }}">
                {{ $cfg['label'] }}
                @if($counts->has($key))
                    <span class="badge bg-secondary ms-1">{{ $counts[$key] }}</span>
                @endif
            </a>
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

    @if($applications->isEmpty())
        <x-ui.empty-state
            icon="person-lines-fill"
            :message="$status ? 'Brak kandydatur o wybranym statusie.' : 'Nie przesłano jeszcze żadnych zgłoszeń rekrutacyjnych.'"
        />
    @else
        <x-ui.card>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Kandydat</th>
                            <th>Stanowisko</th>
                            <th>Telefon</th>
                            <th>Status</th>
                            <th>Data zgłoszenia</th>
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
                                    <span title="{{ $app->created_at->format('Y-m-d H:i') }}">
                                        {{ $app->created_at->diffForHumans() }}
                                    </span>
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

</x-app-layout>
