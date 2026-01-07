<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Szczegóły Przypisania Mieszkania</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold">Pracownik:</dt>
                        <dd>{{ $accommodationAssignment->employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Mieszkanie:</dt>
                        <dd>{{ $accommodationAssignment->accommodation->name }} - {{ $accommodationAssignment->accommodation->city }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Rozpoczęcia:</dt>
                        <dd>{{ $accommodationAssignment->start_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Zakończenia:</dt>
                        <dd>{{ $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                    </div>
                    @if($accommodationAssignment->notes)
                    <div class="md:col-span-2">
                        <dt class="font-semibold">Uwagi:</dt>
                        <dd>{{ $accommodationAssignment->notes }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6">
                    <a href="{{ route('accommodation-assignments.edit', $accommodationAssignment) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                    <a href="{{ route('employees.accommodations.index', $accommodationAssignment->employee_id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
