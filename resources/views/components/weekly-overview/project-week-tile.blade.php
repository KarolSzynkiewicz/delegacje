@props(['weekData', 'project'])

@if($weekData['has_data'])
    <div class="bg-white rounded-xl shadow-lg p-5 border border-gray-200 hover:shadow-xl transition-shadow">
        <!-- Zapotrzebowanie -->
        <x-weekly-overview.requirements-summary 
            :requirementsSummary="$weekData['requirements_summary']"
            :project="$project"
            :week="$weekData['week']"
        />

        <!-- Lista ról -->
        @if($weekData['requirements_summary']['role_details'])
            <x-weekly-overview.role-list 
                :roleDetails="$weekData['requirements_summary']['role_details']"
            />
        @endif

        <!-- Uwagi -->
        <div class="mb-5 p-4 rounded-xl {{ $weekData['requirements_summary']['total_missing'] == 0 ? 'bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500' : 'bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500' }} shadow-sm">
            @if($weekData['requirements_summary']['total_missing'] == 0)
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 font-semibold">Wszystko OK – pełny skład</p>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="text-sm text-yellow-800 font-medium">
                        Uwagi: brak {{ $weekData['requirements_summary']['total_missing'] }} {{ Str::plural('osoby', $weekData['requirements_summary']['total_missing']) }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Przycisk przypisania -->
        <div class="mb-4">
            <a href="{{ route('projects.assignments.create', $project) }}" 
               class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2.5 px-5 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-2 border-blue-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Przypisz osoby
            </a>
        </div>

        <!-- Domy i auta -->
        <x-weekly-overview.resources-section 
            :accommodations="$weekData['accommodations']"
            :vehicles="$weekData['vehicles']"
        />

        <!-- Lista przypisanych osób -->
        @if($weekData['assigned_employees']->isNotEmpty())
            <x-weekly-overview.assigned-employees 
                :assignedEmployees="$weekData['assigned_employees']"
            />
        @endif
    </div>
@else
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl shadow-md p-8 border-2 border-dashed border-gray-300 text-center min-h-[200px] flex flex-col justify-center">
        <div class="mb-4">
            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
        </div>
        <p class="text-gray-600 text-lg font-medium mb-5">Brak prac w tym tygodniu</p>
        @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
            <a href="{{ route('projects.demands.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
               class="inline-flex items-center gap-2 bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-6 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-2 border-green-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Dodaj zapotrzebowanie
            </a>
        @else
            <a href="{{ route('projects.demands.create', $project) }}" 
               class="inline-flex items-center gap-2 bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-3 px-6 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-2 border-green-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Dodaj zapotrzebowanie
            </a>
        @endif
    </div>
@endif

