<div>
    <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Mieszkania</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($search || $statusFilter)
                            Znaleziono: <span class="font-semibold text-gray-900">{{ $accommodations->total() }}</span> mieszkań
                        @else
                            Łącznie: <span class="font-semibold text-gray-900">{{ $accommodations->total() }}</span> mieszkań
                        @endif
                    </p>
                </div>
                @if($search || $statusFilter)
                    <button wire:click="clearFilters" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Wyczyść filtry
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Nazwa, adres, miasto..." class="w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="statusFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="full">Pełne</option>
                    <option value="available">Wolne miejsca</option>
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
                            <button wire:click="sortBy('name')" class="flex items-center space-x-1 hover:text-gray-700">
                                <span>Nazwa</span>
                                @if($sortField === 'name')
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
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adres</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojemność</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($accommodations as $accommodation)
                        @php
                            $currentCount = $accommodation->currentAssignments()->count();
                            $isFull = $currentCount >= $accommodation->capacity;
                            $isOverfilled = $currentCount > $accommodation->capacity;
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 md:px-6 py-4">
                                @if($accommodation->image_path)
                                    <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="rounded ring-2 ring-gray-200 hover:ring-blue-400 transition-all" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded-full bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center ring-2 ring-gray-200 hover:ring-blue-400 transition-all" style="width: 50px; height: 50px;">
                                        <span class="text-white text-xs font-semibold">{{ substr($accommodation->name, 0, 2) }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $accommodation->name }}</div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $accommodation->address }}{{ $accommodation->city ? ', ' . $accommodation->city : '' }}</div>
                            </td>
                            <td class="px-4 md:px-6 py-4">
                                <span class="text-sm {{ $isOverfilled ? 'text-red-600 font-bold' : ($isFull ? 'text-green-600 font-semibold' : 'text-gray-700') }}">
                                    {{ $currentCount }} / {{ $accommodation->capacity }} osób
                                </span>
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                @if($isOverfilled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">Przepełnione</span>
                                @elseif($isFull)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">Pełne</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">Wolne miejsca</span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 transition-colors">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">Zobacz</span>
                                    </a>
                                    <a href="{{ route('accommodations.edit', $accommodation) }}" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                    </svg>
                                    <p class="text-gray-500 text-sm font-medium">
                                        @if($search || $statusFilter)
                                            Brak mieszkań spełniających kryteria
                                        @else
                                            Brak mieszkań
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
            {{ $accommodations->links() }}
        </div>
    </div>
</div>
