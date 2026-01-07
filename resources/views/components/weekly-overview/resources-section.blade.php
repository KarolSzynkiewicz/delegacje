@props(['vehicles'])

@if($vehicles->isNotEmpty())
    <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm mb-5">
        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-lg">
            <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
            </svg>
            Auta w projekcie
        </h4>
        <div class="space-y-4">
            @foreach($vehicles as $vehicleData)
                @php
                    $vehicle = $vehicleData['vehicle'];
                    $employeeCount = $vehicleData['employee_count'];
                    $capacity = $vehicleData['capacity'];
                    $usagePercentage = $vehicleData['usage_percentage'];
                    $isOverfilled = $employeeCount > $capacity;
                    $isFull = $employeeCount == $capacity;
                    $isPartial = $employeeCount > 0 && $employeeCount < $capacity;
                @endphp
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4 mb-3">
                        <!-- Car image or icon -->
                        @if($vehicle->image_path)
                            <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 border-2 border-gray-200 shadow-sm">
                                <img 
                                    src="{{ $vehicle->image_url }}" 
                                    alt="{{ $vehicleData['vehicle_name'] }}"
                                    class="w-full h-full object-cover"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2306b6d4\'%3E%3Cpath d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'/%3E%3C/svg%3E';"
                                >
                            </div>
                        @else
                            <div class="w-16 h-16 bg-cyan-100 rounded-lg flex items-center justify-center flex-shrink-0 border-2 border-gray-200">
                                <svg class="w-10 h-10 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="hover:underline">
                                <h5 class="font-semibold text-gray-800 text-base truncate">{{ $vehicleData['vehicle_name'] }}</h5>
                            </a>
                            @if($vehicleData['driver'])
                                <p class="text-sm text-green-600 font-semibold mt-1">
                                    <span class="text-gray-600">kierowca:</span> 
                                    <a href="{{ route('employees.show', $vehicleData['driver']) }}" class="hover:underline">{{ $vehicleData['driver']->full_name }}</a>
                                </p>
                            @else
                                <p class="text-sm text-red-600 font-semibold mt-1">
                                    Brak kierowcy
                                </p>
                            @endif
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-sm font-bold text-gray-700">{{ $vehicleData['usage'] }}</span>
                        </div>
                    </div>
                    
                    {{-- Dropdown z listą osób w aucie --}}
                    @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->count() > 0)
                        <div class="mb-3">
                            <details class="group">
                                <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 font-medium flex items-center gap-1">
                                    <span>Kto jest w tym aucie? ({{ $vehicleData['assignments']->count() }})</span>
                                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <ul class="mt-2 space-y-1 pl-4 border-l-2 border-gray-200">
                                    @foreach($vehicleData['assignments'] as $assignment)
                                        @php
                                            $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                            $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                            $isDriver = $positionValue === 'driver';
                                        @endphp
                                        <li>
                                            <a href="{{ route('employees.show', $assignment->employee) }}" 
                                               class="text-sm {{ $isDriver ? 'text-green-600 font-semibold' : 'text-blue-600' }} hover:underline flex items-center gap-1">
                                                @if($isDriver)
                                                    🚗 
                                                @endif
                                                {{ $assignment->employee->full_name }}
                                                @if($isDriver)
                                                    <span class="text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded">Kierowca</span>
                                                @endif
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

