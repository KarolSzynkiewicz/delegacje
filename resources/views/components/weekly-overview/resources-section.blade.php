@props(['accommodations', 'vehicles'])

@if($accommodations->isNotEmpty() || $vehicles->isNotEmpty())
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
        @if($accommodations->isNotEmpty())
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-4 border border-purple-200 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Domy w tym tygodniu
                </h4>
                <div class="space-y-2">
                    @foreach($accommodations as $accommodationData)
                        <div class="bg-white rounded-lg p-3 border-l-4 border-purple-500 flex justify-between items-center shadow-sm hover:shadow-md transition">
                            <span class="text-sm font-semibold text-gray-800">{{ $accommodationData['accommodation']->name }}</span>
                            <span class="text-xs bg-purple-100 text-purple-700 font-semibold px-2 py-1 rounded-full">{{ $accommodationData['usage'] }} osób</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($vehicles->isNotEmpty())
            <div class="bg-gradient-to-br from-cyan-50 to-blue-50 rounded-xl p-4 border border-cyan-200 shadow-sm">
                <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                    </svg>
                    Auta w tym tygodniu
                </h4>
                <div class="space-y-2">
                    @foreach($vehicles as $vehicleData)
                        <div class="bg-white rounded-lg p-3 border-l-4 border-cyan-500 flex justify-between items-center shadow-sm hover:shadow-md transition">
                            <span class="text-sm font-semibold text-gray-800">{{ $vehicleData['vehicle_name'] }}</span>
                            <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-1 rounded-full">
                                👤 {{ $vehicleData['driver']->full_name }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif

