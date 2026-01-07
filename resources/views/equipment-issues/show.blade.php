<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wydanie Sprzętu
            </h2>
            <a href="{{ route('equipment-issues.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
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
                            <p class="text-sm text-gray-500">Sprzęt</p>
                            <p class="font-semibold">{{ $equipmentIssue->equipment->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Pracownik</p>
                            <p class="font-semibold">{{ $equipmentIssue->employee->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Ilość</p>
                            <p class="font-semibold">{{ $equipmentIssue->quantity_issued }} {{ $equipmentIssue->equipment->unit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Data wydania</p>
                            <p class="font-semibold">{{ $equipmentIssue->issue_date->format('Y-m-d') }}</p>
                        </div>
                        @if($equipmentIssue->expected_return_date)
                        <div>
                            <p class="text-sm text-gray-500">Oczekiwana data zwrotu</p>
                            <p class="font-semibold">{{ $equipmentIssue->expected_return_date->format('Y-m-d') }}</p>
                        </div>
                        @endif
                        @if($equipmentIssue->actual_return_date)
                        <div>
                            <p class="text-sm text-gray-500">Rzeczywista data zwrotu</p>
                            <p class="font-semibold">{{ $equipmentIssue->actual_return_date->format('Y-m-d') }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="px-2 py-1 text-xs rounded-full 
                                @if($equipmentIssue->status === 'issued') bg-blue-100 text-blue-800
                                @elseif($equipmentIssue->status === 'returned') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($equipmentIssue->status) }}
                            </span>
                        </div>
                        @if($equipmentIssue->projectAssignment)
                        <div>
                            <p class="text-sm text-gray-500">Projekt</p>
                            <p class="font-semibold">{{ $equipmentIssue->projectAssignment->project->name }}</p>
                        </div>
                        @endif
                        @if($equipmentIssue->notes)
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Notatki</p>
                            <p>{{ $equipmentIssue->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                @if($equipmentIssue->status === 'issued')
                <div class="flex items-center justify-end">
                    <a href="{{ route('equipment-issues.return', $equipmentIssue) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Zwróć Sprzęt
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
