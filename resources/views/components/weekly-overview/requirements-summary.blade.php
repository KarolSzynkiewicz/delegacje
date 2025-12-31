@props(['requirementsSummary', 'project', 'week'])

<div class="mb-5">
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-200 shadow-sm">
        <div class="flex justify-between items-center flex-wrap gap-3 mb-3">
            <h4 class="text-lg font-bold text-gray-800">Zapotrzebowanie</h4>
            <a href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $week['start']->format('Y-m-d'), 'date_to' => $week['end']->format('Y-m-d')]) }}" 
               class="text-blue-600 hover:text-blue-800 text-sm font-medium underline decoration-2 underline-offset-2 transition">
                Edytuj zapotrzebowanie
            </a>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700">Potrzebnych:</span>
                <span class="bg-blue-100 text-blue-800 font-bold px-3 py-1 rounded-lg text-base">{{ $requirementsSummary['total_needed'] }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700">Przypisanych:</span>
                <span class="bg-indigo-100 text-indigo-800 font-bold px-3 py-1 rounded-lg text-base">{{ $requirementsSummary['total_assigned'] }}</span>
            </div>
            @if($requirementsSummary['total_missing'] > 0)
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-red-700 font-bold">brak</span>
                    <span class="bg-red-100 text-red-800 font-bold px-3 py-1 rounded-lg text-base">{{ $requirementsSummary['total_missing'] }}</span>
                </div>
            @endif
        </div>
    </div>
</div>

