@props(['roleDetails'])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-5">
    @foreach($roleDetails as $roleDetail)
        <div class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-4 text-center shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-center gap-2 flex-wrap">
                <span class="bg-green-100 text-green-700 font-bold px-3 py-1 rounded-lg text-sm">{{ $roleDetail['needed'] }}</span>
                <span class="text-gray-700 font-medium">{{ $roleDetail['role']->name }}</span>
                <span class="text-gray-400">→</span>
                <span class="bg-blue-100 text-blue-700 font-bold px-3 py-1 rounded-lg text-sm">{{ $roleDetail['assigned'] }}</span>
            </div>
        </div>
    @endforeach
</div>

