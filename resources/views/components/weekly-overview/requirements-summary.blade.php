@props(['requirementsSummary', 'project', 'week'])

<div class="mb-2">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-2 border border-blue-200 shadow-sm">
        <div class="flex justify-between items-center gap-2 mb-1.5">
            <h4 class="text-xs font-bold text-gray-800">Zapotrzebowanie</h4>
            <a href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $week['start']->format('Y-m-d'), 'date_to' => $week['end']->format('Y-m-d')]) }}" 
               class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-[10px] transition shadow-sm hover:shadow-md">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edytuj
            </a>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1">
                <span class="text-[10px] font-medium text-gray-700">Potrzebnych:</span>
                <span class="bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded text-xs">{{ $requirementsSummary['total_needed'] }}</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="text-[10px] font-medium text-gray-700">Przypisanych:</span>
                <span class="bg-indigo-100 text-indigo-800 font-bold px-1.5 py-0.5 rounded text-xs">{{ $requirementsSummary['total_assigned'] }}</span>
            </div>
            @if($requirementsSummary['total_missing'] > 0)
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-medium text-red-700 font-bold">brak</span>
                    <span class="bg-red-100 text-red-800 font-bold px-1.5 py-0.5 rounded text-xs">{{ $requirementsSummary['total_missing'] }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

