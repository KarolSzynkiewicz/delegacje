<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            Przegląd przydziałów ekip – tygodniowy podgląd
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Nawigacja między tygodniami -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <!-- Przycisk poprzedni tydzień -->
                <a href="{{ $navigation['prevUrl'] }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni tydzień</span>
                </a>

                <!-- Aktualny tydzień -->
                <div class="text-center">
                    <h3 class="fs-5 fw-bold text-dark mb-0">
                        Tydzień {{ $navigation['current']['number'] }}
                    </h3>
                    <p class="small text-muted mb-0">
                        {{ $navigation['current']['start']->format('d.m.Y') }} – {{ $navigation['current']['end']->format('d.m.Y') }}
                    </p>
                </div>

                <!-- Przycisk następny tydzień -->
                <a href="{{ $navigation['nextUrl'] }}" class="btn btn-primary d-flex align-items-center gap-2">
                    <span>Następny tydzień</span>
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>

            <!-- Tabela tygodniowa -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <colgroup>
                            <col style="width: 25%;">
                            <col style="width: 75%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-center fw-bold bg-light border-bottom-2">
                                    Projekt
                                </th>
                                <th class="text-center fw-bold bg-light border-bottom-2">
                                    <div>Tydzień {{ $weeks[0]['number'] }}</div>
                                    <div class="small fw-normal text-muted mt-1">{{ $weeks[0]['label'] }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-light bg-opacity-50">
                            @forelse($projects as $projectData)
                                @php
                                    $project = $projectData['project'];
                                @endphp
                                <tr>
                                    <td class="bg-white border-end-2 align-top">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-primary rounded-circle me-2" style="width: 8px; height: 8px; padding: 0;"></span>
                                            <span class="fw-bold text-dark">{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="small text-muted mb-3">
                                                <i class="bi bi-geo-alt"></i> {{ $project->location->name }}
                                            </div>
                                        @endif
                                        
                                        @php
                                            $summary = $projectData['summary'] ?? null;
                                        @endphp
                                        
                                        {{-- Kafelek: Realizacja --}}
                                        <x-weekly-overview.realization-tile :summary="$summary" />
                                        
                                        {{-- Kafelek: Domy --}}
                                        <x-weekly-overview.housing-tile :summary="$summary" />
                                        
                                        {{-- Kafelek: Auta --}}
                                        <x-weekly-overview.vehicles-tile :summary="$summary" />
                                    </td>
                                    <td class="bg-white align-top">
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
            </div>

            <!-- Sekcje dodatkowe -->
            <div class="mt-4">
                <!-- Kończące się dokumenty -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="fs-3 fw-bold text-dark mb-4 text-center d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-exclamation-triangle text-danger"></i>
                            Kończące się dokumenty i ubezpieczenia
                        </h2>
                        <div class="row g-3">
                            <!-- TODO: Implementacja wyświetlania kończących się dokumentów -->
                            <div class="col-md-4">
                                <div class="card border-start border-danger border-4">
                                    <div class="card-body">
                                        <p class="text-dark fw-medium mb-0">Funkcjonalność w przygotowaniu</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
