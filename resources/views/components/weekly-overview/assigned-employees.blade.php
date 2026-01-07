@props(['assignedEmployees'])

<div x-data="{ open: false }" class="mt-5">
    <button 
        @click="open = !open"
        class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3.5 px-5 rounded-xl flex justify-between items-center transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border-2 border-gray-300"
    >
        <span class="flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Pokaż kto tu jest ({{ $assignedEmployees->count() }} {{ Str::plural('osoba', $assignedEmployees->count()) }})
        </span>
        <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="open" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="mt-4 space-y-3">
        <h5 class="font-semibold text-gray-800 mb-3">Przypisane osoby</h5>
        
        @foreach($assignedEmployees as $employeeData)
            <div class="bg-gradient-to-br {{ $employeeData['is_partial'] ? 'from-yellow-50 to-amber-50 border-yellow-300 border-l-4' : 'from-white to-gray-50 border-gray-200' }} rounded-xl p-4 border-2 hover:shadow-lg transition-all">
                <div class="mb-3">
                    <div class="flex items-center gap-3 flex-wrap">
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
                                <a href="{{ route('employees.show', $employeeData['employee']) }}" class="font-bold text-gray-900 text-base hover:underline">{{ $employeeData['employee']->full_name }}</a>
                                <span class="text-gray-600">–</span>
                                <span class="text-gray-700 font-medium">{{ $employeeData['role']->name }}</span>
                                @if($employeeData['is_partial'])
                                    <span class="text-xs text-gray-500 bg-yellow-100 px-2 py-1 rounded-full">({{ $employeeData['date_range'] }})</span>
                                @else
                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">(cały tydzień)</span>
                                @endif
                            </div>
                        </div>
                        <!-- Edit icon -->
                        <button class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="text-sm text-gray-700 mb-4 space-y-1">
                    @if($employeeData['accommodation'])
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span class="font-medium">dom:</span> 
                            <a href="{{ route('accommodations.show', $employeeData['accommodation']) }}" class="hover:underline">{{ $employeeData['accommodation']->name }}</a>
                        </div>
                    @endif
                    @if($employeeData['vehicle'])
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                            </svg>
                            <span class="font-medium">auto:</span> 
                            <a href="{{ route('vehicles.show', $employeeData['vehicle']) }}" class="hover:underline">{{ $employeeData['vehicle']->brand }} {{ $employeeData['vehicle']->model }} {{ $employeeData['vehicle']->registration_number }}</a>
                        </div>
                    @endif
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <button class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition shadow-sm hover:shadow">
                        zmień dom
                    </button>
                    <button class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition shadow-sm hover:shadow">
                        zmień auto
                    </button>
                    <button class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded-lg font-medium transition shadow-sm hover:shadow">
                        zmień projekt
                    </button>
                    <button class="text-xs bg-orange-100 hover:bg-orange-200 text-orange-800 px-3 py-1.5 rounded-lg font-medium transition shadow-sm hover:shadow border border-orange-300">
                        zmień rolę
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

