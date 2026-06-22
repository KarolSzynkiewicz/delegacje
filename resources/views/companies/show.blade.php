<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Spółka: {{ $company->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('companies.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button variant="ghost" href="{{ route('companies.edit', $company) }}" routeName="companies.edit" action="edit">
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Szczegóły Spółki">
        <div class="row mb-3">
            <div class="col-md-6">
                <h5>Nazwa</h5>
                <p><strong>{{ $company->name }}</strong></p>
            </div>
            <div class="col-md-3">
                <h5>NIP</h5>
                <p>{{ $company->nip }}</p>
            </div>
            <div class="col-md-3">
                <h5>REGON</h5>
                <p>{{ $company->regon ?? '-' }}</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <h5>KRS</h5>
                <p>{{ $company->krs ?? '-' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Data założenia</h5>
                <p>{{ $company->founded_at ? $company->founded_at->format('d.m.Y') : '-' }}</p>
            </div>
            <div class="col-md-4">
                <h5>Prezes</h5>
                <p>{{ $company->president_name ?? '-' }}</p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-8">
                <h5>Adres</h5>
                <p>
                    @if($company->address || $company->city)
                        {{ $company->address }}{{ $company->address && $company->city ? ', ' : '' }}{{ $company->postal_code }} {{ $company->city }}
                        @if($company->country) · {{ $company->country->labelWithFlag() }}@endif
                    @else
                        -
                    @endif
                </p>
            </div>
            <div class="col-md-4">
                <h5>Kontakt</h5>
                <p>
                    @if($company->email){{ $company->email }}<br>@endif
                    @if($company->phone){{ $company->phone }}@endif
                    @if(!$company->email && !$company->phone)-@endif
                </p>
            </div>
        </div>

        @if($company->notes)
            <div class="mb-3">
                <h5>Notatki</h5>
                <p>{{ $company->notes }}</p>
            </div>
        @endif
    </x-ui.card>

    <x-ui.card class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Przypisania pracowników</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('companies.show', ['company' => $company->id, 'filter' => $filter === 'active' ? 'all' : 'active']) }}"
                   class="btn btn-sm {{ $filter === 'active' ? 'btn-primary' : 'btn-outline-primary' }}">
                    {{ $filter === 'active' ? 'Aktywne' : 'Wszystkie' }}
                </a>
                <x-ui.button variant="primary" href="{{ route('company-assignments.create', ['company_id' => $company->id]) }}" class="btn-sm">
                    Dodaj przypisanie
                </x-ui.button>
            </div>
        </div>

        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
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
                                <td><x-employee-cell :employee="$assignment->employee" /></td>
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
                                        if ($assignment->isCurrentlyActive()) {
                                            $statusLabel = 'Aktywne';
                                            $badgeVariant = 'success';
                                        } elseif ($assignment->isPast()) {
                                            $statusLabel = 'Zakończone';
                                            $badgeVariant = 'accent';
                                        } elseif ($assignment->isScheduled()) {
                                            $statusLabel = 'Zaplanowane';
                                            $badgeVariant = 'info';
                                        } else {
                                            $statusLabel = 'Nieznany';
                                            $badgeVariant = 'accent';
                                        }
                                    @endphp
                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                </td>
                                <td class="text-end">
                                    <x-ui.button variant="ghost" href="{{ route('company-assignments.show', $assignment) }}" class="btn-sm">Szczegóły</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $assignments->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <x-ui.empty-state icon="inbox" message="Brak przypisań pracowników do tej spółki." />
        @endif
    </x-ui.card>

    <x-ui.card class="mt-4">
        <x-comments :commentable="$company" />
    </x-ui.card>
</x-app-layout>
