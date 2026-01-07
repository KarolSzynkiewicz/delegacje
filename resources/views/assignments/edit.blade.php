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
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('project_id') border-red-500 @enderror">
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ old('project_id', $assignment->project_id) == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('project_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik</label>
                        <select name="employee_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('employee_id') border-red-500 @enderror">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id', $assignment->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}@if($employee->roles->count() > 0) ({{ $employee->roles->pluck('name')->join(', ') }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Rola w Projekcie</label>
                        <select name="role_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('role_id') border-red-500 @enderror">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $assignment->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Rozpoczęcia</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('start_date') border-red-500 @enderror">
                        @error('start_date')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Zakończenia (opcjonalnie)</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('end_date') border-red-500 @enderror">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('status') border-red-500 @enderror">
                            @php
                                $currentStatus = $assignment->status instanceof \App\Enums\AssignmentStatus 
                                    ? $assignment->status->value 
                                    : ($assignment->status ?? 'active');
                                $oldStatus = old('status', $currentStatus);
                            @endphp
                            <option value="active" {{ $oldStatus == 'active' ? 'selected' : '' }}>Aktywny</option>
                            <option value="in_transit" {{ $oldStatus == 'in_transit' ? 'selected' : '' }}>W transporcie</option>
                            <option value="at_base" {{ $oldStatus == 'at_base' ? 'selected' : '' }}>W bazie</option>
                            <option value="completed" {{ $oldStatus == 'completed' ? 'selected' : '' }}>Zakończony</option>
                            <option value="cancelled" {{ $oldStatus == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Uwagi</label>
                        <textarea name="notes" rows="3"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes', $assignment->notes) }}</textarea>
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
