<div>
    <!-- Statystyki i Filtry -->
    <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Pojazdy</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($search || $conditionFilter || $statusFilter)
                            Znaleziono: <span class="font-semibold text-gray-900">{{ $vehicles->total() }}</span> pojazdów
                        @else
                            Łącznie: <span class="font-semibold text-gray-900">{{ $vehicles->total() }}</span> pojazdów
                        @endif
                    </p>
                </div>
                @if($search || $conditionFilter || $statusFilter)
                    <button wire:click="clearFilters" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Wyczyść filtry
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Szukaj
                    </span>
                </label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nr rej., marka, model..." class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stan techniczny</label>
                <select wire:model.live="conditionFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="excellent">Doskonały</option>
                    <option value="good">Dobry</option>
                    <option value="fair">Zadowalający</option>
                    <option value="poor">Słaby</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="statusFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="occupied">Zajęty</option>
                    <option value="available">Wolny</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg relative">
        <div wire:loading.delay class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center z-10 rounded-lg">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-8 w-8 text-blue-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 font-medium">Ładowanie...</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zdjęcie</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            <button wire:click="sortBy('registration_number')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Nr Rejestracyjny</span>
                                @if($sortField === 'registration_number')
                                    @if($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @endif
                            </button>
                        </th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marka i Model</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stan</th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojemność</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($vehicles as $vehicle)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 md:px-6 py-4">
                                @if($vehicle->image_path)
                                    <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}" class="rounded ring-2 ring-gray-200 hover:ring-blue-400 transition-all" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center ring-2 ring-gray-200 hover:ring-blue-400 transition-all" style="width: 50px; height: 50px;">
                                        <span class="text-white text-xs font-semibold">{{ substr($vehicle->registration_number, 0, 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $vehicle->registration_number }}</div>
                                <div class="md:hidden text-xs text-gray-500 mt-1">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4">
                                <div class="text-sm text-gray-900">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</div>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                                @php
                                    $labels = ['excellent' => 'Doskonały', 'good' => 'Dobry', 'fair' => 'Zadowalający', 'poor' => 'Słaby'];
                                    $colors = ['excellent' => 'green', 'good' => 'blue', 'fair' => 'yellow', 'poor' => 'red'];
                                    $color = $colors[$vehicle->technical_condition] ?? 'gray';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 border border-{{ $color }}-200">
                                    {{ $labels[$vehicle->technical_condition] ?? $vehicle->technical_condition }}
                                </span>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-900">{{ $vehicle->capacity ?? '-' }} osób</td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                @if($vehicle->currentAssignment())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Zajęty</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Wolny</span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Zobacz</span>
                                    </a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Edytuj</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                    <p class="text-gray-500 text-sm font-medium">
                                        @if($search || $conditionFilter || $statusFilter)
                                            Brak pojazdów spełniających kryteria
                                        @else
                                            Brak pojazdów
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 md:px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $vehicles->links() }}
        </div>
    </div>
</div>
