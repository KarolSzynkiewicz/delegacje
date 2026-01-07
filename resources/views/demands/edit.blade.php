<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edytuj zapotrzebowanie dla projektu: {{ $demand->project->name }}
            </h2>
            <a href="{{ route('projects.demands.index', $demand->project) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('demands.update', $demand) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <h4 class="text-red-800 font-semibold mb-2">Wystąpiły błędy:</h4>
                            <ul class="list-disc list-inside text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rola <span class="text-red-500">*</span></label>
                        <select name="role_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $demand->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data od <span class="text-red-500">*</span></label>
                            <input type="date" name="date_from" value="{{ old('date_from', $demand->date_from->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('date_from')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do (opcjonalnie)</label>
                            <input type="date" name="date_to" value="{{ old('date_to', $demand->date_to ? $demand->date_to->format('Y-m-d') : '') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('date_to')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ilość osób <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            name="required_count" 
                            value="{{ old('required_count', $demand->required_count) }}" 
                            min="0" 
                            step="1"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Ustaw 0 aby usunąć zapotrzebowanie</p>
                        @error('required_count')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uwagi (opcjonalnie)</label>
                        <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $demand->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end items-center mt-6">
                        <a href="{{ route('projects.demands.index', $demand->project) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zaktualizuj zapotrzebowanie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
