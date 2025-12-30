<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @isset($employee)
                    Mieszkania pracownika: {{ $employee->full_name }}
                @else
                    Wszystkie przypisania mieszkań
                @endisset
            </h2>
            @isset($employee)
                <a href="{{ route('employees.accommodations.create', $employee) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Przypisz Mieszkanie</a>
            @endisset
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if(!isset($employee))
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mieszkanie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Od - Do</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($assignments as $assignment)
                            <tr>
                                @if(!isset($employee))
                                    <td class="px-6 py-4">
                                        <a href="{{ route('employees.show', $assignment->employee) }}" class="text-blue-600 hover:text-blue-900">
                                            {{ $assignment->employee->full_name }}
                                        </a>
                                    </td>
                                @endif
                                <td class="px-6 py-4">{{ $assignment->accommodation->name }} ({{ $assignment->accommodation->city }})</td>
                                <td class="px-6 py-4">{{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('accommodation-assignments.edit', $assignment) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                    <form action="{{ route('accommodation-assignments.destroy', $assignment) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ isset($employee) ? 3 : 4 }}" class="px-6 py-4 text-center text-gray-500">Brak przypisanych mieszkań</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
