<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj Wpis Ewidencji Godzin
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('time-logs.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Przypisanie do projektu *</label>
                        <select name="project_assignment_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz przypisanie</option>
                            @foreach($assignments as $assignment)
                                <option value="{{ $assignment->id }}" {{ old('project_assignment_id') == $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->employee->full_name }} - {{ $assignment->project->name }} 
                                    ({{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data pracy *</label>
                        <input type="date" name="work_date" value="{{ old('work_date', date('Y-m-d')) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Liczba godzin *</label>
                        <input type="number" name="hours_worked" value="{{ old('hours_worked') }}" step="0.25" min="0" max="24" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <p class="text-sm text-gray-500 mt-1">Wprowadź liczbę godzin (0-24, możesz użyć 0.25 dla 15 minut)</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('time-logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
