<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edytuj Przypisanie</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('assignments.update', $assignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Projekt</label>
                        <select name="project_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $assignment->project_id == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik</label>
                        <select name="employee_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ $assignment->employee_id == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}@if($employee->roles->count() > 0) ({{ $employee->roles->pluck('name')->join(', ') }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Rola w Projekcie</label>
                        <select name="role_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ $assignment->role_id == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Rozpoczęcia</label>
                        <input type="date" name="start_date" value="{{ $assignment->start_date->format('Y-m-d') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Zakończenia (opcjonalnie)</label>
                        <input type="date" name="end_date" value="{{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '' }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="pending" {{ $assignment->status == 'pending' ? 'selected' : '' }}>Oczekujące</option>
                            <option value="active" {{ $assignment->status == 'active' ? 'selected' : '' }}>Aktywne</option>
                            <option value="completed" {{ $assignment->status == 'completed' ? 'selected' : '' }}>Zakończone</option>
                            <option value="cancelled" {{ $assignment->status == 'cancelled' ? 'selected' : '' }}>Anulowane</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Uwagi</label>
                        <textarea name="notes" rows="3"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ $assignment->notes }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Aktualizuj
                        </button>
                        <a href="{{ route('assignments.index') }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
