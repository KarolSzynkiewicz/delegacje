<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Ewidencja Godzin
            </h2>
            <a href="{{ route('time-logs.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Dodaj Wpis
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projekt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Godziny</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($timeLogs as $timeLog)
                                <tr>
                                    <td class="px-6 py-4">{{ $timeLog->start_time->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4">{{ $timeLog->projectAssignment->employee->full_name }}</td>
                                    <td class="px-6 py-4">{{ $timeLog->projectAssignment->project->name }}</td>
                                    <td class="px-6 py-4 font-semibold">{{ number_format($timeLog->hours_worked, 2) }}h</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('time-logs.show', $timeLog) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                        <a href="{{ route('time-logs.edit', $timeLog) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                        @can('delete', $timeLog)
                                        <form action="{{ route('time-logs.destroy', $timeLog) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        Brak wpisów
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $timeLogs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
