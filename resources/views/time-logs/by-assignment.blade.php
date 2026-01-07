<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ewidencja Godzin: {{ $assignment->employee->full_name }} - {{ $assignment->project->name }}
            </h2>
            <a href="{{ route('time-logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            <strong>Okres przypisania:</strong> {{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                        </p>
                        <p class="text-sm text-gray-600 mt-2">
                            <strong>Suma godzin:</strong> {{ number_format($timeLogs->sum('hours_worked'), 2) }}h
                        </p>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Godziny</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notatki</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($timeLogs as $timeLog)
                                <tr>
                                    <td class="px-6 py-4">{{ $timeLog->start_time->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 font-semibold">{{ number_format($timeLog->hours_worked, 2) }}h</td>
                                    <td class="px-6 py-4">{{ $timeLog->notes ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('time-logs.show', $timeLog) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                        <a href="{{ route('time-logs.edit', $timeLog) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        Brak wpisów dla tego przypisania
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
