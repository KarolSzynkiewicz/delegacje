<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Szczegóły Zjazdu
            </h2>
            <a href="{{ route('return-trips.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje podstawowe</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Data zjazdu</p>
                            <p class="font-semibold">{{ $returnTrip->event_date->format('Y-m-d H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                {{ $returnTrip->status->label() }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pojazd</p>
                            <p class="font-semibold">
                                {{ $returnTrip->vehicle ? $returnTrip->vehicle->registration_number . ' - ' . $returnTrip->vehicle->brand . ' ' . $returnTrip->vehicle->model : '-' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Z</p>
                            <p class="font-semibold">{{ $returnTrip->fromLocation->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Do</p>
                            <p class="font-semibold">{{ $returnTrip->toLocation->name }}</p>
                        </div>
                        @if($returnTrip->notes)
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Notatki</p>
                            <p>{{ $returnTrip->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Uczestnicy ({{ $returnTrip->participants->count() }})</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Przypisanie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($returnTrip->participants as $participant)
                                <tr>
                                    <td class="px-6 py-4">{{ $participant->employee->full_name }}</td>
                                    <td class="px-6 py-4">
                                        @if($participant->assignment)
                                            {{ class_basename($participant->assignment) }} #{{ $participant->assignment_id }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                            {{ ucfirst($participant->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
