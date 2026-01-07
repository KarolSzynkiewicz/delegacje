@props(['weekData', 'project'])

@if($weekData['has_data'])
    <div class="bg-white rounded-xl shadow-lg p-3 border border-gray-200 hover:shadow-xl transition-shadow w-full">
        <!-- Zapotrzebowanie, role i uwagi w jednym bloku -->
        <div class="mb-2">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-2 border border-blue-200 shadow-sm">
                <div class="flex justify-between items-center gap-2 mb-2">
                    <h4 class="text-xs font-bold text-gray-800">Zapotrzebowanie</h4>
                    <a href="{{ route('projects.demands.create', ['project' => $project, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" 
                       class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-2 rounded text-[10px] transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edytuj
                    </a>
                </div>
                
                <!-- Tabelka zapotrzebowania -->
                @if(!empty($weekData['requirements_summary']['role_details']))
                    <div class="mb-2 overflow-x-auto">
                        <table class="w-full text-[10px] border-collapse">
                            <thead>
                                <tr class="bg-blue-100">
                                    <th class="text-left py-1 px-2 font-semibold text-gray-800 border border-blue-200">Rola</th>
                                    <th class="text-center py-1 px-2 font-semibold text-gray-800 border border-blue-200">Potrzebnych</th>
                                    <th class="text-center py-1 px-2 font-semibold text-gray-800 border border-blue-200">Przypisanych</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach($weekData['requirements_summary']['role_details'] as $roleDetail)
                                    @php
                                        $needed = $roleDetail['needed'];
                                        $assigned = $roleDetail['assigned'];
                                        $isComplete = $assigned >= $needed;
                                        $isPartial = $assigned > 0 && $assigned < $needed;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-1 px-2 text-gray-700 border border-gray-200">{{ Str::lower($roleDetail['role']->name) }}</td>
                                        <td class="py-1 px-2 text-center font-semibold {{ $isComplete ? 'text-green-700' : ($isPartial ? 'text-yellow-700' : 'text-red-700') }} border border-gray-200">
                                            {{ $needed }}
                                        </td>
                                        <td class="py-1 px-2 text-center font-semibold text-blue-700 border border-gray-200">
                                            {{ $assigned }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-100 font-bold">
                                    <td class="py-1 px-2 text-gray-800 border border-gray-300">łącznie</td>
                                    <td class="py-1 px-2 text-center text-gray-800 border border-gray-300">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                    <td class="py-1 px-2 text-center text-gray-800 border border-gray-300">{{ $weekData['requirements_summary']['total_assigned'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Brak ról - pokaż tylko podsumowanie -->
                    <div class="mb-2 overflow-x-auto">
                        <table class="w-full text-[10px] border-collapse">
                            <thead>
                                <tr class="bg-blue-100">
                                    <th class="text-left py-1 px-2 font-semibold text-gray-800 border border-blue-200">Rola</th>
                                    <th class="text-center py-1 px-2 font-semibold text-gray-800 border border-blue-200">Potrzebnych</th>
                                    <th class="text-center py-1 px-2 font-semibold text-gray-800 border border-blue-200">Przypisanych</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <tr class="bg-gray-100 font-bold">
                                    <td class="py-1 px-2 text-gray-800 border border-gray-300">łącznie</td>
                                    <td class="py-1 px-2 text-center text-gray-800 border border-gray-300">{{ $weekData['requirements_summary']['total_needed'] }}</td>
                                    <td class="py-1 px-2 text-center text-gray-800 border border-gray-300">{{ $weekData['requirements_summary']['total_assigned'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Uwagi -->
                <div class="mt-1.5 p-1.5 rounded {{ $weekData['requirements_summary']['total_missing'] == 0 ? 'bg-green-100 border-l-2 border-green-500' : 'bg-yellow-100 border-l-2 border-yellow-500' }}">
                    @if($weekData['requirements_summary']['total_missing'] == 0)
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3 h-3 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-[10px] text-green-800 font-semibold">Wszystko OK – pełny skład</p>
                        </div>
                    @else
                        <div>
                            <div class="flex items-center gap-1.5 mb-1">
                                <svg class="w-3 h-3 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <p class="text-[10px] text-yellow-800 font-medium">
                                    Brak {{ $weekData['requirements_summary']['total_missing'] }} osób
                                </p>
                            </div>
                            @if(!empty($weekData['requirements_summary']['role_details']))
                                @php
                                    $missingRoles = array_filter($weekData['requirements_summary']['role_details'], function($roleDetail) {
                                        return $roleDetail['missing'] > 0;
                                    });
                                @endphp
                                @if(!empty($missingRoles))
                                    <div class="text-[10px] text-yellow-700 ml-4.5">
                                        @foreach($missingRoles as $roleDetail)
                                            <div>
                                                • {{ Str::lower($roleDetail['role']->name) }}: brak {{ $roleDetail['missing'] }} 
                                                @php
                                                    $missing = $roleDetail['missing'];
                                                    if ($missing == 1) {
                                                        echo 'osoby';
                                                    } else {
                                                        echo 'osób';
                                                    }
                                                @endphp
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dwie kolumny: Osoby i Auta -->
        <div class="grid grid-cols-2 gap-3 mb-3">
            <!-- Kolumna 1: Osoby w projekcie -->
            <div class="bg-white rounded-lg p-2 border border-gray-200 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-1 text-xs">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Osoby
                </h4>
                @if($weekData['assigned_employees']->isNotEmpty())
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @foreach($weekData['assigned_employees']->take(8) as $employeeData)
                            <div class="flex items-center gap-2 p-1.5 bg-gradient-to-r from-gray-50 to-white rounded border border-gray-200">
                                @if($employeeData['employee']->image_path)
                                    <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0 border border-gray-300">
                                @else
                                    <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 border border-gray-300">
                                        <span class="text-orange-600 font-semibold text-[10px]">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs">
                                        <a href="{{ route('employees.show', $employeeData['employee']) }}" class="font-semibold text-gray-900 hover:underline">{{ $employeeData['employee']->full_name }}</a>
                                        <span class="text-gray-500 text-[10px]"> – {{ $employeeData['role']->name }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($weekData['assigned_employees']->count() > 8)
                            <div class="text-center pt-1">
                                <span class="text-[10px] text-gray-500">+{{ $weekData['assigned_employees']->count() - 8 }} więcej</span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-2">
                        @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                            <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-2 rounded text-[10px] transition w-full justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                            </a>
                        @else
                            <a href="{{ route('projects.assignments.create', $project) }}" class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-2 rounded text-[10px] transition w-full justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                {{ $weekData['assigned_employees']->count() > 0 ? 'Dodaj' : 'Przypisz' }}
                            </a>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500 text-xs">
                        Brak osób
                    </div>
                    <div class="mt-2">
                        @if(isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end']))
                            <a href="{{ route('projects.assignments.create', ['project' => $project->id, 'date_from' => $weekData['week']['start']->format('Y-m-d'), 'date_to' => $weekData['week']['end']->format('Y-m-d')]) }}" class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-2 rounded text-[10px] transition w-full justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Przypisz osoby
                            </a>
                        @else
                            <a href="{{ route('projects.assignments.create', $project) }}" class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-2 rounded text-[10px] transition w-full justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Przypisz osoby
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Kolumna 2: Auta w projekcie -->
            <div class="bg-white rounded-lg p-2 border border-gray-200 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-1 text-xs">
                    <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                    </svg>
                    Auta
                </h4>
                @if($weekData['vehicles']->isNotEmpty())
                    <div class="space-y-1.5 max-h-32 overflow-y-auto mb-2">
                        @foreach($weekData['vehicles']->take(8) as $vehicleData)
                            <div class="p-1.5 bg-gradient-to-r from-gray-50 to-white rounded border border-gray-200">
                                <!-- Wrapper na auto, ikonkę, ilość i kierowcę -->
                                <div class="flex items-center gap-2 mb-1.5">
                                    <!-- Vehicle image or icon -->
                                    @if($vehicleData['vehicle']->image_path)
                                        <img src="{{ $vehicleData['vehicle']->image_url }}" 
                                             alt="{{ $vehicleData['vehicle_name'] }}" 
                                             class="w-8 h-8 rounded object-cover flex-shrink-0 border border-gray-300"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2306b6d4\'%3E%3Cpath d=\'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2\'/%3E%3C/svg%3E';">
                                    @else
                                        <div class="w-8 h-8 bg-cyan-100 rounded flex items-center justify-center flex-shrink-0 border border-gray-300">
                                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 text-xs">
                                        <a href="{{ route('vehicles.show', $vehicleData['vehicle']) }}" class="font-semibold text-gray-900 truncate hover:underline block">{{ $vehicleData['vehicle_name'] }}</a>
                                        <div class="text-[10px] text-gray-600 mt-0.5">
                                            {{ $vehicleData['usage'] }}
                                            @if($vehicleData['driver'])
                                                <span class="text-green-600"> • <a href="{{ route('employees.show', $vehicleData['driver']) }}" class="hover:underline">{{ $vehicleData['driver']->full_name }}</a></span>
                                            @else
                                                <span class="text-red-600"> • Brak kierowcy</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Dropdown z listą osób w aucie - poniżej --}}
                                @if(isset($vehicleData['assignments']) && $vehicleData['assignments']->count() > 0)
                                    <details class="mt-1.5 group">
                                        <summary class="cursor-pointer text-[10px] text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 underline">
                                            <span>Kto jest w tym aucie? ({{ $vehicleData['assignments']->count() }})</span>
                                            <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </summary>
                                        <ul class="mt-1 space-y-0.5 pl-2 border-l-2 border-gray-200">
                                            @foreach($vehicleData['assignments'] as $assignment)
                                                @php
                                                    $position = $assignment->position ?? \App\Enums\VehiclePosition::PASSENGER;
                                                    $positionValue = $position instanceof \App\Enums\VehiclePosition ? $position->value : $position;
                                                    $isDriver = $positionValue === 'driver';
                                                @endphp
                                                <li>
                                                    <a href="{{ route('employees.show', $assignment->employee) }}" 
                                                       class="text-[10px] {{ $isDriver ? 'text-green-600 font-semibold' : 'text-blue-600' }} hover:underline flex items-center gap-1">
                                                        @if($isDriver)
                                                            🚗 
                                                        @endif
                                                        {{ $assignment->employee->full_name }}
                                                        @if($isDriver)
                                                            <span class="text-[9px] bg-green-100 text-green-800 px-1 py-0.5 rounded">Kierowca</span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                        @if($weekData['vehicles']->count() > 8)
                            <div class="text-center pt-1">
                                <span class="text-[10px] text-gray-500">+{{ $weekData['vehicles']->count() - 8 }} więcej</span>
                            </div>
                        @endif
                    </div>
                @endif
                
                <!-- Bez auta -->
                @php
                    $employeesWithoutVehicle = $weekData['assigned_employees']->filter(function($employeeData) {
                        return empty($employeeData['vehicle']);
                    });
                @endphp
                @if($employeesWithoutVehicle->isNotEmpty())
                    <div class="pt-2 border-t border-gray-200">
                        <div class="text-[10px] font-semibold text-gray-700 mb-1.5">Bez auta:</div>
                        <div class="space-y-1 max-h-32 overflow-y-auto">
                            @foreach($employeesWithoutVehicle as $employeeData)
                                <div class="flex items-center gap-2 p-1 bg-gray-50 rounded border border-gray-200">
                                    @if($employeeData['employee']->image_path)
                                        <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0 border border-gray-300">
                                    @else
                                        <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 border border-gray-300">
                                            <span class="text-orange-600 font-semibold text-[10px]">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 text-xs">
                                        <a href="{{ route('employees.show', $employeeData['employee']) }}" class="font-semibold text-gray-900 truncate hover:underline block">{{ $employeeData['employee']->full_name }}</a>
                                        <div class="text-[10px] text-gray-500">{{ $employeeData['role']->name }}</div>
                                    </div>
                                    @php
                                        $employee = $employeeData['employee'];
                                        $url = route('employees.vehicles.create', $employee->id);
                                        if (isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end'])) {
                                            $url .= '?date_from=' . $weekData['week']['start']->format('Y-m-d') . '&date_to=' . $weekData['week']['end']->format('Y-m-d');
                                        }
                                    @endphp
                                    <a href="{{ $url }}" 
                                       class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-0.5 px-1.5 rounded text-[10px] transition flex-shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Auto
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($weekData['assigned_employees']->isNotEmpty())
                    <div class="pt-2 border-t border-gray-200">
                        <div class="text-[10px] text-green-700 font-medium">
                            ✓ Wszyscy mają przypisane auto
                        </div>
                    </div>
                @elseif($weekData['vehicles']->isEmpty())
                    <div class="text-center py-4 text-gray-500 text-xs">
                        Brak aut
                    </div>
                @endif
            </div>
        </div>

        <!-- Domy w projekcie (pełna szerokość pod spodem) -->
        @if($weekData['accommodations']->isNotEmpty() || $weekData['assigned_employees']->isNotEmpty())
            <div class="bg-white rounded-lg p-2 border border-gray-200 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-1 text-xs">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Domy w projekcie
                </h4>
                @if($weekData['accommodations']->isNotEmpty())
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        @foreach($weekData['accommodations'] as $accommodationData)
                            @php
                                $accommodation = $accommodationData['accommodation'];
                                $employeeCount = $accommodationData['employee_count'];
                                $capacity = $accommodationData['capacity'];
                                $usagePercentage = $accommodationData['usage_percentage'];
                                $isOverfilled = $employeeCount > $capacity;
                                $isFull = $employeeCount == $capacity;
                                $isPartial = $employeeCount > 0 && $employeeCount < $capacity;
                            @endphp
                            <div class="p-2 bg-gradient-to-r from-gray-50 to-white rounded border border-gray-200 flex items-center gap-2">
                                <!-- Accommodation image or icon -->
                                @if($accommodation->image_path)
                                    <img src="{{ $accommodation->image_url }}" 
                                         alt="{{ $accommodation->name }}" 
                                         class="w-8 h-8 rounded object-cover flex-shrink-0 border border-gray-300"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2310b981\'%3E%3Cpath d=\'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6\'/%3E%3C/svg%3E';">
                                @else
                                    <div class="w-8 h-8 bg-teal-100 rounded flex items-center justify-center flex-shrink-0 border border-gray-300">
                                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0 text-xs">
                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="font-semibold text-gray-900 truncate hover:underline block">{{ $accommodation->name }}</a>
                                    <div class="text-[10px] text-gray-600 mt-0.5">{{ $accommodationData['usage'] }}</div>
                                    {{-- Dropdown z listą osób w domu --}}
                                    @if(isset($accommodationData['assignments']) && $accommodationData['assignments']->count() > 0)
                                        <details class="mt-1.5 group">
                                            <summary class="cursor-pointer text-[10px] text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 underline">
                                                <span>Kto jest w tym domu? ({{ $accommodationData['assignments']->count() }})</span>
                                                <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </summary>
                                            <ul class="mt-1 space-y-0.5 pl-2 border-l-2 border-gray-200">
                                                @foreach($accommodationData['assignments'] as $assignment)
                                                    <li>
                                                        <a href="{{ route('employees.show', $assignment->employee) }}" 
                                                           class="text-[10px] text-blue-600 hover:underline flex items-center gap-1">
                                                            🏠 {{ $assignment->employee->full_name }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                
                <!-- Bez domu -->
                @php
                    $employeesWithoutAccommodation = $weekData['assigned_employees']->filter(function($employeeData) {
                        return empty($employeeData['accommodation']);
                    });
                @endphp
                @if($employeesWithoutAccommodation->isNotEmpty())
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="text-[10px] font-semibold text-gray-700 mb-1.5">Bez domu:</div>
                        <div class="space-y-1">
                            @foreach($employeesWithoutAccommodation as $employeeData)
                                <div class="flex items-center gap-2 p-1 bg-gray-50 rounded border border-gray-200">
                                    @if($employeeData['employee']->image_path)
                                        <img src="{{ $employeeData['employee']->image_url }}" alt="{{ $employeeData['employee']->full_name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0 border border-gray-300">
                                    @else
                                        <div class="w-6 h-6 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0 border border-gray-300">
                                            <span class="text-orange-600 font-semibold text-[10px]">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0 text-xs">
                                        <a href="{{ route('employees.show', $employeeData['employee']) }}" class="font-semibold text-gray-900 truncate hover:underline block">{{ $employeeData['employee']->full_name }}</a>
                                        <div class="text-[10px] text-gray-500">{{ $employeeData['role']->name }}</div>
                                    </div>
                                    @php
                                        $employee = $employeeData['employee'];
                                        $url = route('employees.accommodations.create', $employee->id);
                                        if (isset($weekData['week']) && isset($weekData['week']['start']) && isset($weekData['week']['end'])) {
                                            $url .= '?date_from=' . $weekData['week']['start']->format('Y-m-d') . '&date_to=' . $weekData['week']['end']->format('Y-m-d');
                                        }
                                    @endphp
                                    <a href="{{ $url }}" 
                                       class="inline-flex items-center gap-1 bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-0.5 px-1.5 rounded text-[10px] transition flex-shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Dom
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif($weekData['assigned_employees']->isNotEmpty())
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="text-[10px] text-green-700 font-medium">
                            ✓ Wszyscy mają przypisany dom
                        </div>
                    </div>
                @endif
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

