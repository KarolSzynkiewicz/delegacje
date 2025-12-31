<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Przegląd przydziałów ekip – tygodniowy podgląd
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[92%] mx-auto px-4">
            <!-- Nawigacja między tygodniami -->
            <div class="mb-6 flex justify-between items-center gap-4">
                @php
                    $prevWeekStart = $weeks[0]['start']->copy()->subWeeks(3)->startOfWeek();
                    $prevWeekEnd = $prevWeekStart->copy()->addWeeks(2)->endOfWeek();
                    $nextWeekStart = $weeks[2]['end']->copy()->addDay()->startOfWeek();
                    $nextWeekEnd = $nextWeekStart->copy()->addWeeks(2)->endOfWeek();
                @endphp
                
                <!-- Przycisk poprzednie 3 tygodnie -->
                <a href="{{ route('weekly-overview.index', ['start_date' => $prevWeekStart->format('Y-m-d')]) }}" class="inline-flex items-center gap-3 bg-gradient-to-r from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 text-gray-700 hover:text-gray-900 font-bold text-lg px-6 py-3 rounded-xl transition-all shadow-md hover:shadow-lg border-2 border-gray-300 group">
                    <svg class="w-5 h-5 transform group-hover:-translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                    </svg>
                    <div class="flex flex-col items-start">
                        <span>Poprzednie 3 tygodnie</span>
                        <span class="text-gray-600 text-sm font-normal">({{ $prevWeekStart->format('d.m') }} – {{ $prevWeekEnd->format('d.m.Y') }})</span>
                    </div>
                </a>

                <!-- Przycisk następne 3 tygodnie -->
                <a href="{{ route('weekly-overview.index', ['start_date' => $nextWeekStart->format('Y-m-d')]) }}" class="inline-flex items-center gap-3 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 text-blue-700 hover:text-blue-900 font-bold text-lg px-6 py-3 rounded-xl transition-all shadow-md hover:shadow-lg border-2 border-blue-300 group">
                    <div class="flex flex-col items-end">
                        <span>Następne 3 tygodnie</span>
                        <span class="text-blue-600 text-sm font-normal">({{ $nextWeekStart->format('d.m') }} – {{ $nextWeekEnd->format('d.m.Y') }})</span>
                    </div>
                    <svg class="w-5 h-5 transform group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>

            <!-- Tabela tygodniowa -->
            <div class="bg-white shadow-xl rounded-2xl mb-8 border border-gray-100 w-full">
                <div class="w-full">
                    <table class="w-full table-fixed">
                        <colgroup>
                            <col style="width: 18%;">
                            @foreach($weeks as $week)
                                <col style="width: calc(82% / {{ count($weeks) }});">
                            @endforeach
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="px-3 py-4 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-900 text-center font-bold text-base rounded-tl-2xl border-b-2 border-gray-300">
                                    Projekt
                                </th>
                                @foreach($weeks as $index => $week)
                                    <th class="px-3 py-4 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-900 text-center font-bold text-base border-b-2 border-gray-300 {{ $loop->last ? 'rounded-tr-2xl' : '' }}" style="vertical-align: middle;">
                                        <div>Tydzień {{ $week['number'] }}</div>
                                        <div class="text-xs font-normal text-gray-700 mt-1">{{ $week['label'] }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-gray-50">
                            @forelse($projects as $projectData)
                                @php
                                    $project = $projectData['project'];
                                @endphp
                                <tr class="hover:bg-gray-100 transition-colors">
                                    <td class="px-3 py-4 bg-white border-2 border-gray-300 border-r-0 text-gray-900 font-bold text-base align-top shadow-sm rounded-l-2xl" style="vertical-align: top;">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-2 flex-shrink-0"></div>
                                            <span class="break-words">{{ $project->name }}</span>
                                        </div>
                                        @if($project->location)
                                            <div class="text-xs font-normal text-gray-600 mt-1">
                                                📍 {{ $project->location->name }}
                                            </div>
                                        @endif
                                    </td>
                                    @foreach($projectData['weeks_data'] as $weekData)
                                        <td class="px-2 py-3 border-2 border-gray-300 {{ $loop->first ? 'border-l-0' : '' }} {{ $loop->last ? 'border-r-2 rounded-r-2xl' : 'border-r-0' }}" style="vertical-align: top;">
                                            <x-weekly-overview.project-week-tile 
                                                :weekData="$weekData" 
                                                :project="$project" 
                                            />
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($weeks) + 1 }}" class="px-6 py-12 text-center text-gray-500 text-lg">
                                        Brak projektów do wyświetlenia
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sekcje dodatkowe -->
            <div class="space-y-6 mt-12">
                <!-- Kończące się dokumenty -->
                <div class="bg-white overflow-hidden shadow-xl rounded-2xl p-8 border border-gray-100">
                    <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center flex items-center justify-center gap-3">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Kończące się dokumenty i ubezpieczenia
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- TODO: Implementacja wyświetlania kończących się dokumentów -->
                        <div class="p-5 bg-gradient-to-br from-red-50 to-pink-50 border-l-4 border-red-500 rounded-xl shadow-sm">
                            <p class="text-gray-700 font-medium">Funkcjonalność w przygotowaniu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
