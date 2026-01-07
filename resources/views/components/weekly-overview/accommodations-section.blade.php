@props(['accommodations'])

@if($accommodations->isNotEmpty())
    <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm mb-5">
        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
            <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Domy w projekcie
        </h4>
        <div class="space-y-4">
            @foreach($accommodations as $accommodationData)
                @php
                    $accommodation = $accommodationData['accommodation'];
                    $employeeCount = $accommodationData['employee_count'];
                    $capacity = $accommodationData['capacity'];
                    $usagePercentage = $accommodationData['usage_percentage'];
                    $isOverfilled = $employeeCount > $capacity;
                    $isFull = $employeeCount == $capacity;
                    $isPartial = $employeeCount > 0 && $employeeCount < $capacity;
                @endphp
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4 mb-3">
                        <!-- House image or icon -->
                        @if($accommodation->image_path)
                            <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 border-2 border-gray-200 shadow-sm">
                                <img 
                                    src="{{ $accommodation->image_url }}" 
                                    alt="{{ $accommodation->name }}"
                                    class="w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2310b981\'%3E%3Cpath d=\'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\'/%3E%3C/svg%3E';"
                                >
                            </div>
                        @else
                            <div class="w-16 h-16 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0 border-2 border-gray-200">
                                <svg class="w-10 h-10 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('accommodations.show', $accommodation) }}" class="hover:underline">
                                <h5 class="font-semibold text-gray-800 text-base truncate">{{ $accommodation->name }}</h5>
                            </a>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-sm font-bold text-gray-700">{{ $accommodationData['usage'] }}</span>
                        </div>
                    </div>
                    
                    {{-- Dropdown z listą osób w domu --}}
                    @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->count() > 0)
                        <div class="mb-3">
                            <details class="group">
                                <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium flex items-center gap-1">
                                    <span>Kto jest w tym domu? ({{ $accommodationData['assignments']->count() }})</span>
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <ul class="mt-2 space-y-1 pl-4 border-l-2 border-gray-200">
                                    @foreach($accommodationData['assignments'] as $assignment)
                                        <li>
                                            <a href="{{ route('employees.show', $assignment->employee) }}" 
                                               class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                                                🏠 {{ $assignment->employee->full_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        </div>
                    @endif
                    
                    <!-- Progress bar -->
                    <div class="relative w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div 
                            class="absolute top-0 left-0 h-full rounded-full transition-all duration-300 {{ $isOverfilled ? 'bg-red-500' : ($isFull ? 'bg-green-500' : ($isPartial ? 'bg-yellow-500' : 'bg-transparent')) }}"
                            style="width: {{ min($usagePercentage, 100) }}%"
                        ></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xs font-semibold {{ $isOverfilled || $isFull ? 'text-white' : 'text-gray-800' }}">{{ $employeeCount }}/{{ $capacity }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

