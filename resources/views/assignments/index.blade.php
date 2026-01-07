<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @isset($project)
                    Pracownicy w projekcie: {{ $project->name }}
                @else
                    Wszystkie przypisania
                @endisset
            </h2>
            @isset($project)
                <a href="{{ route('projects.assignments.create', $project) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Przypisz Pracownika</a>
            @endisset
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @isset($project)
                {{-- Widok dla konkretnego projektu - bez Livewire --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Od - Do</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($assignments as $assignment)
                                <tr>
                                    <td class="px-6 py-4">{{ $assignment->employee->full_name }}</td>
                                    <td class="px-6 py-4">{{ $assignment->role->name }}</td>
                                    <td class="px-6 py-4">{{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}</td>
                                    <td class="px-6 py-4">
                                        @php
                                            $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                            $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                            $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($statusValue === 'active') bg-green-100 text-green-800
                                            @elseif($statusValue === 'completed') bg-blue-100 text-blue-800
                                            @elseif($statusValue === 'cancelled') bg-red-100 text-red-800
                                            @elseif($statusValue === 'in_transit') bg-yellow-100 text-yellow-800
                                            @elseif($statusValue === 'at_base') bg-gray-100 text-gray-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('assignments.show', $assignment) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                        <a href="{{ route('assignments.edit', $assignment) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                        <form action="{{ route('assignments.destroy', $assignment) }}" method="POST" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Brak przypisanych pracowników</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $assignments->links() }}
                    </div>
                </div>
            @else
                {{-- Globalny widok - z Livewire i filtrowaniem --}}
                <livewire:assignments-table />
            @endisset
        </div>
    </div>
</x-app-layout>
