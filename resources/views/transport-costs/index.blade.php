<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Koszty Transportu
            </h2>
            <a href="{{ route('transport-costs.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Dodaj Koszt
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kwota</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zdarzenie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($costs as $cost)
                                <tr>
                                    <td class="px-6 py-4">{{ $cost->cost_date->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4">{{ ucfirst($cost->cost_type) }}</td>
                                    <td class="px-6 py-4 font-semibold">{{ number_format($cost->amount, 2) }} {{ $cost->currency }}</td>
                                    <td class="px-6 py-4">
                                        @if($cost->logisticsEvent)
                                            <a href="{{ route('return-trips.show', $cost->logisticsEvent) }}" class="text-blue-600 hover:text-blue-900">
                                                Zdarzenie #{{ $cost->logisticsEvent->id }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">{{ $cost->description ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('transport-costs.show', $cost) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                        <a href="{{ route('transport-costs.edit', $cost) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                        @can('delete', $cost)
                                        <form action="{{ route('transport-costs.destroy', $cost) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                        </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Brak kosztów
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $costs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
