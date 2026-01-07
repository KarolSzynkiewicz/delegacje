<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Zjazdy (Return Trips)
            </h2>
            <a href="{{ route('return-trips.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Utwórz Zjazd
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojazd</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Z</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Do</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uczestnicy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($returnTrips as $trip)
                                <tr>
                                    <td class="px-6 py-4">{{ $trip->event_date->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4">{{ $trip->vehicle ? $trip->vehicle->registration_number : '-' }}</td>
                                    <td class="px-6 py-4">{{ $trip->fromLocation->name }}</td>
                                    <td class="px-6 py-4">{{ $trip->toLocation->name }}</td>
                                    <td class="px-6 py-4">{{ $trip->participants->count() }} osób</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            {{ $trip->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('return-trips.show', $trip) }}" class="text-blue-600 hover:text-blue-900">
                                            Zobacz
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Brak zjazdów
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $returnTrips->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
