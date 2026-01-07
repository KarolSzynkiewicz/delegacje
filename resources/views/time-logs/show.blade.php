<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wpis Ewidencji Godzin
            </h2>
            <div>
                <a href="{{ route('time-logs.edit', $timeLog) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Edytuj
                </a>
                <a href="{{ route('time-logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje podstawowe</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Pracownik</p>
                            <p class="font-semibold">{{ $timeLog->projectAssignment->employee->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Projekt</p>
                            <p class="font-semibold">{{ $timeLog->projectAssignment->project->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Data pracy</p>
                            <p class="font-semibold">{{ $timeLog->start_time->format('Y-m-d') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Liczba godzin</p>
                            <p class="font-semibold text-lg">{{ number_format($timeLog->hours_worked, 2) }}h</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Godziny</p>
                            <p class="font-semibold">
                                {{ $timeLog->start_time->format('H:i') }} - {{ $timeLog->end_time->format('H:i') }}
                            </p>
                        </div>
                        @if($timeLog->notes)
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Notatki</p>
                            <p>{{ $timeLog->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
