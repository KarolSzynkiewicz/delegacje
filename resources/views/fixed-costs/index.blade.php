<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszty Stałe">
            <x-slot name="right">
                <div class="d-flex gap-2">
                    @if($activeTab === 'templates')
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('fixed-costs.generate') }}"
                            routeName="fixed-costs.generate"
                            action="filter"
                        >
                            Generuj Koszty Stałe
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('fixed-costs.create') }}"
                            routeName="fixed-costs.create"
                            action="create"
                        >
                            Dodaj Szablon
                        </x-ui.button>
                    @else
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('fixed-cost-entries.create') }}"
                            routeName="fixed-cost-entries.create"
                            action="create"
                        >
                            Dodaj Koszt Niestandardowy
                        </x-ui.button>
                    @endif
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <x-ui.card>
        <!-- Zakładki -->
        @php
            $activeTab = $activeTab ?? 'templates';
            $tabsForComponent = [
                'templates' => [
                    'label' => 'Szablony',
                    'icon' => 'bi bi-file-earmark-text',
                    'count' => $templates->total(),
                    'href' => route('fixed-costs.tab.templates'),
                ],
                'entries' => [
                    'label' => 'Koszty Księgowe',
                    'icon' => 'bi bi-journal-text',
                    'count' => $entries->total(),
                    'href' => route('fixed-costs.tab.entries'),
                ],
            ];
        @endphp
        <x-ui.tabs 
            :tabs="$tabsForComponent" 
            :activeTab="$activeTab" 
            id="fixedCostsTabs"
        />

        <div class="tab-content">
            <!-- Tab: Szablony -->
            <div class="tab-pane fade {{ $activeTab === 'templates' ? 'show active' : '' }}" id="templates-tab" role="tabpanel">
                <!-- Statystyki i Filtry -->
                <div class="mb-4 pb-3 border-top border-bottom">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="fs-5 fw-semibold mb-1">Szablony Kosztów Stałych</h3>
                            <p class="small text-muted mb-0">
                                Łącznie: <span class="fw-semibold">{{ $templates->total() }}</span> szablonów
                            </p>
                        </div>
                    </div>
                </div>

                @if($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nazwa</th>
                                    <th>Kwota</th>
                                    <th>Interwał</th>
                                    <th>Dzień</th>
                                    <th>Okres obowiązywania</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $template)
                                    <tr>
                                        <td>{{ $template->name }}</td>
                                        <td class="fw-semibold">{{ number_format($template->amount, 2) }} {{ $template->currency }}</td>
                                        <td>
                                            @if($template->interval_type === 'monthly')
                                                Miesięczny
                                            @elseif($template->interval_type === 'weekly')
                                                Tygodniowy
                                            @else
                                                Roczny
                                            @endif
                                        </td>
                                        <td>{{ $template->interval_day }}</td>
                                        <td>
                                            @if($template->start_date)
                                                {{ $template->start_date->format('Y-m-d') }}
                                            @else
                                                od zawsze
                                            @endif
                                            @if($template->end_date)
                                                - {{ $template->end_date->format('Y-m-d') }}
                                            @else
                                                - bieżące
                                            @endif
                                        </td>
                                        <td>
                                            @if($template->is_active)
                                                <x-ui.badge variant="success">Aktywny</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="secondary">Nieaktywny</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            <x-action-buttons
                                                viewRoute="{{ route('fixed-costs.show', $template) }}"
                                                editRoute="{{ route('fixed-costs.edit', $template) }}"
                                                deleteRoute="{{ route('fixed-costs.destroy', $template) }}"
                                                deleteMessage="Czy na pewno chcesz usunąć ten szablon?"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($templates->hasPages())
                        <div class="mt-3">
                            <x-ui.pagination :paginator="$templates" />
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="folder-x"
                        message="Brak szablonów kosztów stałych w systemie"
                    >
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('fixed-costs.create') }}"
                            routeName="fixed-costs.create"
                            action="create"
                        >
                            Dodaj pierwszy szablon
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </div>

            <!-- Tab: Koszty Księgowe -->
            <div class="tab-pane fade {{ $activeTab === 'entries' ? 'show active' : '' }}" id="entries-tab" role="tabpanel">
                <!-- Statystyki i Filtry -->
                <div class="mb-4 pb-3 border-top border-bottom">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <h3 class="fs-5 fw-semibold mb-1">Koszty Księgowe</h3>
                            <p class="small text-muted mb-0">
                                Łącznie: <span class="fw-semibold">{{ $entries->total() }}</span> kosztów księgowych
                            </p>
                        </div>
                    </div>
                </div>

                @if($entries->count() > 0)
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Nazwa</th>
                                    <th>Kwota</th>
                                    <th>Okres</th>
                                    <th>Data księgowania</th>
                                    <th>Szablon</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td>{{ $entry->name }}</td>
                                        <td class="fw-semibold">{{ number_format($entry->amount, 2) }} {{ $entry->currency }}</td>
                                        <td>
                                            {{ $entry->period_start->format('Y-m-d') }} - {{ $entry->period_end->format('Y-m-d') }}
                                        </td>
                                        <td>{{ $entry->accounting_date->format('Y-m-d') }}</td>
                                        <td>
                                            @if($entry->template)
                                                <a href="{{ route('fixed-costs.show', $entry->template) }}" class="text-decoration-none">
                                                    {{ $entry->template->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Brak szablonu</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-action-buttons
                                                viewRoute="{{ route('fixed-cost-entries.show', $entry) }}"
                                                deleteRoute="{{ route('fixed-cost-entries.destroy', $entry) }}"
                                                deleteMessage="Czy na pewno chcesz usunąć ten koszt księgowy?"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($entries->hasPages())
                        <div class="mt-3">
                            <x-ui.pagination :paginator="$entries" />
                        </div>
                    @endif
                @else
                    <x-ui.empty-state 
                        icon="folder-x"
                        message="Brak wygenerowanych kosztów księgowych"
                    >
                        <x-ui.button 
                            variant="primary" 
                            href="{{ route('fixed-costs.generate') }}"
                            routeName="fixed-costs.generate"
                        >
                            Generuj Koszty Stałe
                        </x-ui.button>
                    </x-ui.empty-state>
                @endif
            </div>
        </div>
    </x-ui.card>
</x-app-layout>

