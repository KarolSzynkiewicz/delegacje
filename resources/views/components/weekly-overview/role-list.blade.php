@props(['roleDetails'])

@if(!empty($roleDetails))
    <div class="mb-2">
        <div class="flex flex-wrap gap-1.5">
            @foreach($roleDetails as $roleDetail)
                @php
                    $needed = $roleDetail['needed'];
                    $assigned = $roleDetail['assigned'];
                    $missing = $roleDetail['missing'];
                    $percentage = $needed > 0 ? round(($assigned / $needed) * 100, 0) : 0;
                    $isComplete = $assigned >= $needed;
                    $isPartial = $assigned > 0 && $assigned < $needed;
                @endphp
                <div class="bg-white border border-gray-200 rounded px-2 py-1 shadow-sm flex items-center gap-1.5">
                    <span class="text-[10px] font-semibold {{ $isComplete ? 'text-green-700' : ($isPartial ? 'text-yellow-700' : 'text-red-700') }}">
                        {{ $needed }}
                    </span>
                    <span class="text-[10px] text-gray-700 truncate max-w-[60px]">{{ Str::lower($roleDetail['role']->name) }}</span>
                    <span class="text-gray-400 text-[10px]">→</span>
                    <span class="text-[10px] font-semibold text-blue-700">{{ $assigned }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

