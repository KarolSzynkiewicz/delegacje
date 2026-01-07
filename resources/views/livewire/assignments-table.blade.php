<div>
    <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Filtry</h3>
            <button wire:click="clearFilters" class="text-sm text-gray-600 hover:text-gray-900">
                Wyczyść filtry
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Pracownik -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pracownik</label>
                <input type="text" wire:model.live.debounce.300ms="searchEmployee" 
                    placeholder="Szukaj pracownika..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Projekt -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Projekt</label>
                <input type="text" wire:model.live.debounce.300ms="searchProject" 
                    placeholder="Szukaj projektu..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Rola -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rola</label>
                <input type="text" wire:model.live.debounce.300ms="searchRole" 
                    placeholder="Szukaj roli..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="status" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="active">Aktywne</option>
                    <option value="completed">Zakończone</option>
                    <option value="cancelled">Anulowane</option>
                </select>
            </div>

            <!-- Data od -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data od</label>
                <input type="date" wire:model.live="dateFrom" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Data do -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Data do</label>
                <input type="date" wire:model.live="dateTo" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projekt</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Od - Do</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($assignments as $assignment)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('employees.show', $assignment->employee) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $assignment->employee->full_name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $assignment->role->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('projects.show', $assignment->project) }}" class="text-blue-600 hover:text-blue-900">
                                    {{ $assignment->project->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $assignment->start_date->format('Y-m-d') }} - 
                                {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
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
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Brak przypisań</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        </div>
    </div>
</div>
