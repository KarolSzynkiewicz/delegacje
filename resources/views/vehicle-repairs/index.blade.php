<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Serwisowanie pojazdów">
            <x-slot name="right">
                <x-ui.button
                    variant="primary"
                    href="{{ route('vehicle-repairs.create') }}"
                    routeName="vehicle-repairs.create"
                    action="create"
                >
                    Nowa akcja serwisowa
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    {{-- Filters --}}
    <div class="mb-4">
        <x-ui.card>
            <form method="GET" action="{{ route('vehicle-repairs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Pojazd</label>
                    <select name="vehicle_id" class="form-select">
                        <option value="">Wszystkie pojazdy</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                                {{ $v->registration_number }}{{ $v->brand ? ' – ' . $v->brand . ' ' . $v->model : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Wszystkie</option>
                        <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>W trakcie</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Zakończone</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Typ akcji</label>
                    <select name="action_type" class="form-select">
                        <option value="">Wszystkie typy</option>
                        @foreach(\App\Enums\ServiceActionType::cases() as $type)
                            <option value="{{ $type->value }}" {{ request('action_type') === $type->value ? 'selected' : '' }}>
                                {{ $type->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtruj</button>
                    <a href="{{ route('vehicle-repairs.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </x-ui.card>
    </div>

    <x-ui.card>
        @if($repairs->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pojazd</th>
                            <th>Typ</th>
                            <th>Warsztat</th>
                            <th>Okres</th>
                            <th>Status</th>
                            <th>Koszt</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repairs as $repair)
                            <tr>
                                <td>
                                    <a href="{{ route('vehicles.show', $repair->vehicle) }}" class="text-decoration-none">
                                        <strong>{{ $repair->vehicle->registration_number }}</strong>
                                        @if($repair->vehicle->brand)
                                            <br><small class="text-muted">{{ $repair->vehicle->brand }} {{ $repair->vehicle->model }}</small>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    <x-ui.badge variant="{{ $repair->action_type->badgeVariant() }}">
                                        {{ $repair->action_type->label() }}
                                    </x-ui.badge>
                                </td>
                                <td>
                                    @if($repair->location)
                                        {{ $repair->location->name }}
                                        @if($repair->location->city)
                                            <br><small class="text-muted">{{ $repair->location->city }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    <small>
                                        {{ $repair->start_date->format('Y-m-d') }}
                                        @if($repair->end_date)
                                            → {{ $repair->end_date->format('Y-m-d') }}
                                        @else
                                            → <em class="text-muted">trwa...</em>
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    <x-ui.badge variant="{{ $repair->status_badge_variant }}">
                                        {{ $repair->status_label }}
                                    </x-ui.badge>
                                </td>
                                <td>
                                    @if($repair->price)
                                        {{ number_format($repair->price, 2) }} {{ $repair->currency }}
                                    @else
                                        <span class="text-muted">–</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.show', $repair) }}" class="btn-sm">
                                            Szczegóły
                                        </x-ui.button>
                                        @if(!$repair->isCompleted())
                                            <x-ui.button variant="success" href="{{ route('vehicle-repairs.complete-form', $repair) }}" class="btn-sm">
                                                Zakończ
                                            </x-ui.button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($repairs->hasPages())
                <div class="mt-3 pt-3 border-top">
                    {{ $repairs->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @else
            <x-ui.empty-state
                icon="tools"
                message="Brak wpisów w książce serwisowej."
            />
        @endif
    </x-ui.card>
</x-app-layout>
