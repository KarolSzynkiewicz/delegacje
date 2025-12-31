<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Przegląd przydziałów ekip – tygodniowy podgląd
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Tabela tygodniowa -->
            <div class="bg-white overflow-hidden shadow-xl rounded-2xl mb-8 border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-5 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-900 text-center font-bold text-lg rounded-tl-2xl border-b-2 border-gray-300">
                                    Projekt
                                </th>
                                @foreach($weeks as $index => $week)
                                    <th class="px-6 py-5 bg-gradient-to-r from-gray-100 to-gray-200 text-gray-900 text-center font-bold text-lg border-b-2 border-gray-300 {{ $loop->last ? 'rounded-tr-2xl' : '' }}">
                                        Tydzień {{ $week['number'] }}<br>
                                        <span class="text-sm font-normal text-gray-700">{{ $week['label'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-gray-50">
                            @forelse($projects as $projectData)
                                @php
                                    $project = $projectData['project'];
                                @endphp
                                <tr class="hover:bg-gray-100 transition-colors {{ !$loop->last ? 'mb-4' : '' }}">
                                    <td class="px-6 py-6 bg-white border-2 border-gray-300 border-r-0 text-gray-900 font-bold text-xl align-top shadow-sm rounded-l-2xl">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                            {{ $project->name }}
                                        </div>
                                        @if($project->location)
                                            <div class="text-sm font-normal text-gray-600 mt-2">
                                                📍 {{ $project->location->name }}
                                            </div>
                                        @endif
                                    </td>
                                    @foreach($projectData['weeks_data'] as $weekData)
                                        <td class="px-4 py-4 align-top border-2 border-gray-300 {{ $loop->first ? 'border-l-0' : '' }} {{ $loop->last ? 'border-r-2 rounded-r-2xl' : 'border-r-0' }}">
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
                <!-- Link do kolejnych tygodni -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 overflow-hidden shadow-xl rounded-2xl p-8 text-center border border-blue-200">
                    <a href="#" class="inline-flex items-center gap-3 text-blue-700 hover:text-blue-900 font-bold text-xl transition group">
                        <span>Przejdź do kolejnych 3 tygodni</span>
                        <span class="text-blue-600 text-sm font-normal">({{ $weeks[2]['end']->copy()->addDay()->format('d.m') }} – {{ $weeks[2]['end']->copy()->addWeeks(3)->format('d.m.Y') }})</span>
                        <svg class="w-6 h-6 transform group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>

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
