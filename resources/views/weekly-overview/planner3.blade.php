<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            Przegląd przydziałów ekip – tygodniowy podgląd (uczciwa agregacja)
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <!-- Komunikat o uproszczeniu -->
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle fs-5"></i>
                    <div>
                        <strong>Widok tygodniowy upraszcza przypisania.</strong>
                        <p class="mb-0 small">Szczegóły mogą się różnić w poszczególnych dniach. Kliknij na dzień lub osobę, aby zobaczyć szczegóły dzienne.</p>
                    </div>
                </div>
            </div>

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
                            @forelse($projectsWithStability as $projectData)
                                @php
                                    $project = $projectData['project'];
                                    $stability = $projectData['stability'] ?? null;
                                @endphp
                                
                                @if($stability && ($stability['demands']->isNotEmpty() || $stability['assignments']->isNotEmpty()))
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
                                                $weekStart = $weeks[0]['start']->format('Y-m-d');
                                            @endphp
                                            
                                            {{-- Link do szczegółów --}}
                                            <div class="mb-3">
                                                <a href="{{ route('weekly-overview.planner2', ['start_date' => $weekStart, 'project_id' => $project->id]) }}" 
                                                   class="btn btn-sm btn-outline-primary w-100">
                                                    <i class="bi bi-calendar-week"></i>
                                                    Zobacz szczegóły dzienne
                                                </a>
                                            </div>
                                            
                                            {{-- Kafelek: Realizacja --}}
                                            <x-weekly-overview.realization-tile-stable :requirementsSummary="$stability['requirements_summary'] ?? null" />
                                            
                                            {{-- Kafelek: Domy --}}
                                            <x-weekly-overview.housing-tile-stable :stability="$stability" :weekStart="$weeks[0]['start']" :weekEnd="$weeks[0]['end']" />
                                            
                                            {{-- Kafelek: Auta --}}
                                            <x-weekly-overview.vehicles-tile-stable :stability="$stability" :weekStart="$weeks[0]['start']" :weekEnd="$weeks[0]['end']" />
                                        </td>
                                        <td class="bg-white align-top">
                                            <x-weekly-overview.project-week-tile-stable 
                                                :stability="$stability" 
                                                :project="$project" 
                                                :weekStart="$weeks[0]['start']"
                                                :weekEnd="$weeks[0]['end']"
                                            />
                                        </td>
                                    </tr>
                                @endif
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
        </div>
    </div>
</x-app-layout>
