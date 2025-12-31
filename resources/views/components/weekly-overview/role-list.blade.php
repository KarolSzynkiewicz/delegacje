@props(['roleDetails'])

<div class="mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($roleDetails as $roleDetail)
            @php
                $needed = $roleDetail['needed'];
                $assigned = $roleDetail['assigned'];
                $missing = $roleDetail['missing'];
                $percentage = $needed > 0 ? round(($assigned / $needed) * 100, 0) : 0;
                $isComplete = $assigned >= $needed;
                $isPartial = $assigned > 0 && $assigned < $needed;
            @endphp
            <div class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <span class="text-sm font-semibold {{ $isComplete ? 'text-green-700' : ($isPartial ? 'text-yellow-700' : 'text-red-700') }}">
                            {{ $needed }}
                        </span>
                        <span class="text-gray-700 font-medium text-sm truncate">{{ Str::lower($roleDetail['role']->name) }}</span>
                        <span class="text-gray-400">→</span>
                        <span class="text-sm font-semibold text-blue-700">{{ $assigned }}</span>
                    </div>
                </div>
                <!-- Progress bar -->
                <div class="relative w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div 
                        class="absolute top-0 left-0 h-full rounded-full transition-all duration-300 {{ $isComplete ? 'bg-green-500' : ($isPartial ? 'bg-yellow-500' : 'bg-red-500') }}"
                        style="width: {{ min($percentage, 100) }}%"
                    ></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

