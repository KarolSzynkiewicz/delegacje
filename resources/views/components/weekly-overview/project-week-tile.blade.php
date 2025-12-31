@props(['weekData', 'project'])

@if($weekData['has_data'])
    <div class="bg-white rounded-xl shadow-lg p-3 border border-gray-200 hover:shadow-xl transition-shadow w-full">
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
        <div class="mb-3 p-2 rounded-xl {{ $weekData['requirements_summary']['total_missing'] == 0 ? 'bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500' : 'bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500' }} shadow-sm">
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
        <div class="mb-2">
            @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                   class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1.5 px-3 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-2 border-blue-300 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Przypisz osoby
                </a>
            @else
                <a href="{{ route('projects.assignments.create', $project) }}" 
                   class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1.5 px-3 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 border-2 border-blue-300 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Przypisz osoby
                </a>
            @endif
        </div>

        <!-- Domy -->
        <x-weekly-overview.accommodations-section 
            :accommodations="$weekData['accommodations']"
        />

        <!-- Auta -->
        <x-weekly-overview.resources-section 
            :vehicles="$weekData['vehicles']"
        />

        <!-- Lista przypisanych osób -->
        @if($weekData['assigned_employees']->isNotEmpty())
            <div class="mb-3">
                <div class="bg-white rounded-xl p-3 border border-gray-200 shadow-sm">
                    <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-2 text-sm">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Osoby w projekcie
                    </h4>
                    <div class="space-y-3">
                        @foreach($weekData['assigned_employees']->take(5) as $employeeData)
                            <div class="flex items-center gap-3 p-3 bg-gradient-to-r from-gray-50 to-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                                <!-- Employee photo or initials -->
                                @if($employeeData['employee']->image_path)
                                    <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 border-gray-300">
                                @else
                                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 border-2 border-gray-300">
                                        <span class="text-orange-600 font-semibold text-xs">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-gray-900">{{ $employeeData['employee']->full_name }}</span>
                                        <span class="text-gray-500">–</span>
                                        <span class="text-gray-700 text-sm">{{ $employeeData['role']->name }}</span>
                                    </div>
                                </div>
                                <!-- Edit icon -->
                                <button class="text-gray-400 hover:text-gray-600 transition flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                        @if($weekData['assigned_employees']->count() > 5)
                            <div class="text-center pt-2">
                                <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                    + Pokaż więcej ({{ $weekData['assigned_employees']->count() - 5 }})
                                </button>
                            </div>
                        @endif
                    </div>
                    @if($weekData['assigned_employees']->count() > 5)
                        <div class="mt-4">
                            <a href="#" class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2 px-4 rounded-lg text-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Przypisz kolejne osoby
                            </a>
                        </div>
                    @else
                        <div class="mt-4">
                            @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                                <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2 px-4 rounded-lg text-sm transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Przypisz kolejne osoby
                                </a>
                            @else
                                <a href="{{ route('projects.assignments.create', $project) }}" class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2 px-4 rounded-lg text-sm transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Przypisz kolejne osoby
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Dropdown z pełną listą osób (opcjonalnie) -->
        @if($weekData['assigned_employees']->count() > 5)
            <x-weekly-overview.assigned-employees 
                :assignedEmployees="$weekData['assigned_employees']"
            />
        @endif
    </div>
@else
    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl shadow-md p-8 border-2 border-dashed border-gray-300 text-center min-h-[200px] flex flex-col justify-center w-full">
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

