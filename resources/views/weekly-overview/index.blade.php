<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2 align-items-center">
                @if($projectId)
                    <x-ui.button variant="ghost" href="{{ route('weekly-overview.index', ['start_date' => $startDate->format('Y-m-d')]) }}" class="btn-sm">
                        <i class="bi bi-x-circle"></i> Wyczyść filtry
                    </x-ui.button>
                @endif
            </div>
            <div class="flex-grow-1 text-center">
                <h2 class="fw-semibold fs-4 mb-0">
                    Przegląd tygodniowy
                </h2>
            </div>
            <div class="d-flex align-items-center">
                <select id="project-search" class="form-select form-select-sm" style="width: 200px;" onchange="(function() { const baseUrl = '{{ route('weekly-overview.index') }}'; const params = new URLSearchParams(); params.set('start_date', '{{ $startDate->format('Y-m-d') }}'); if (this.value) { params.set('project_id', this.value); } window.location.href = baseUrl + '?' + params.toString(); }).call(this)">
                    <option value="">Wszystkie projekty</option>
                    @foreach($allProjects as $project)
                        <option value="{{ $project->id }}" {{ $projectId && $projectId == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Nawigacja między tygodniami -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <!-- Przycisk poprzedni tydzień -->
                <x-ui.button variant="ghost" href="{{ $navigation['prevUrl'] }}">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni tydzień</span>
                </x-ui.button>

                <!-- Aktualny tydzień -->
                <div class="text-center">
                    <h3 class="fs-5 fw-bold mb-0">
                        Tydzień {{ $navigation['current']['number'] }}
                    </h3>
                    <p class="small text-muted mb-0">
                        {{ $navigation['current']['start']->format('d.m.Y') }} – {{ $navigation['current']['end']->format('d.m.Y') }}
                    </p>
                </div>

                <!-- Przycisk następny tydzień -->
                <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}">
                    <span>Następny tydzień</span>
                    <i class="bi bi-chevron-right"></i>
                </x-ui.button>
            </div>

            <!-- Tabela tygodniowa -->
            <x-ui.card class="mb-4">
                <div class="table-responsive">
                    <table class="table">
                        <colgroup>
                            <col style="width: 25%;">
                            <col style="width: 75%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center fw-bold">
                                    Projekt
                                </th>
                                <th class="text-center fw-bold">
                                    <div>Tydzień {{ $weeks[0]['number'] }}</div>
                                    <div class="small fw-normal text-muted mt-1">{{ $weeks[0]['label'] }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($projects as $projectData)
                                @php
                                    $project = $projectData['project'];
                                @endphp
                                <tr>
                                    <td class="align-top">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary rounded-circle me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                            <span class="fw-bold">{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="small text-muted mb-3">
                                                <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                            </div>
                                        @endif
                                        
                                        @php
                                            $summary = $projectData['summary'] ?? null;
                                            $weekStart = $weeks[0]['start']->format('Y-m-d');
                                        @endphp
                                        
                                        {{-- Link do szczegółów --}}
                                        <div class="mb-3">
                                            <x-ui.button variant="ghost" href="{{ route('weekly-overview.planner2', ['start_date' => $weekStart, 'project_id' => $project->id]) }}" class="w-100 btn-sm">
                                                <i class="bi bi-calendar-week"></i>
                                                Zobacz szczegóły
                                            </x-ui.button>
                                        </div>
                                        
                                        {{-- Kafelek: Realizacja --}}
                                        <x-weekly-overview.realization-tile :summary="$summary" />
                                        
                                        {{-- Kafelek: Domy --}}
                                        <x-weekly-overview.housing-tile :summary="$summary" />
                                        
                                        {{-- Kafelek: Auta --}}
                                        <x-weekly-overview.vehicles-tile :summary="$summary" />
                                    </td>
                                    <td class="align-top">
                                        @php
                                            $weekStart = $weeks[0]['start']->format('Y-m-d');
                                            $planner2Url = route('weekly-overview.planner2', ['start_date' => $weekStart, 'project_id' => $project->id]);
                                        @endphp
                                        <x-ui.alert variant="warning" title="Ostrzeżenie" class="mb-3">
                                            Widok tygodniowy upraszcza przypisania. Szczegóły mogą się różnić w poszczególnych dniach. 
                                            W <a href="{{ $planner2Url }}" class="text-warning" style="text-decoration: underline;">widoku dziennym</a> widać dokładne przypisania dla każdego dnia.
                                        </x-ui.alert>
                                        <x-weekly-overview.project-week-tile 
                                            :weekData="$projectData['weeks_data'][0]" 
                                            :project="$project" 
                                        />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-5 text-muted">
                                        Brak projektów do wyświetlenia
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <!-- Sekcje dodatkowe -->
            <div class="mt-4">
                <!-- Kończące się dokumenty -->
                <x-ui.card>
                        <h2 class="fs-3 fw-bold mb-4 text-center d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Kończące się dokumenty i ubezpieczenia
                        </h2>
                        <div class="row g-3">
                            <!-- TODO: Implementacja wyświetlania kończących się dokumentów -->
                            <div class="col-md-4">
                                <x-ui.card>
                                    <p class="fw-medium mb-0">Funkcjonalność w przygotowaniu</p>
                                </x-ui.card>
                            </div>
                        </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
