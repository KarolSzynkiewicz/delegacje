<div class="mt-4 p-4 border rounded-lg {{ $isAvailable === null ? 'bg-gray-50' : ($isAvailable ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200') }}">
    <h4 class="font-semibold mb-2">Status Dostępności</h4>
    
    @if($isAvailable === null)
        <p class="text-sm text-gray-600">Wybierz pracownika i datę rozpoczęcia, aby sprawdzić dostępność.</p>
    @elseif($isAvailable)
        <div class="flex items-center text-green-700">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>Pracownik jest dostępny w wybranym terminie.</span>
        </div>
    @else
        <div class="text-red-700">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-bold">Konflikt terminów!</span>
            </div>
            <p class="text-sm mb-2">Pracownik ma już przypisania w tym okresie:</p>
            <ul class="text-xs list-disc list-inside">
                @foreach($conflicts as $conflict)
                    <li>{{ $conflict->project->name }} ({{ $conflict->start_date->format('Y-m-d') }} - {{ $conflict->end_date ? $conflict->end_date->format('Y-m-d') : '...' }})</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
