<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wydania Sprzętu
            </h2>
            <a href="{{ route('equipment-issues.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Wydaj Sprzęt
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sprzęt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ilość</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data wydania</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($issues as $issue)
                                <tr>
                                    <td class="px-6 py-4">{{ $issue->equipment->name }}</td>
                                    <td class="px-6 py-4">{{ $issue->employee->full_name }}</td>
                                    <td class="px-6 py-4">{{ $issue->quantity_issued }} {{ $issue->equipment->unit }}</td>
                                    <td class="px-6 py-4">{{ $issue->issue_date->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($issue->status === 'issued') bg-blue-100 text-blue-800
                                            @elseif($issue->status === 'returned') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($issue->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('equipment-issues.show', $issue) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                        @if($issue->status === 'issued')
                                            <a href="{{ route('equipment-issues.return', $issue) }}" class="text-green-600 hover:text-green-900">Zwróć</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Brak wydań
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $issues->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
