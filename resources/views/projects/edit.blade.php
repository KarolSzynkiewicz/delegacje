<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edytuj Projekt</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('projects.update', $project) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nazwa Projektu</label>
                        <input type="text" name="name" value="{{ old('name', $project->name) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Klient</label>
                        <input type="text" name="client_name" value="{{ old('client_name', $project->client_name) }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Opis</label>
                        <textarea name="description" rows="4"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('description', $project->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="active" {{ $project->status == 'active' ? 'selected' : '' }}>Aktywny</option>
                            <option value="on_hold" {{ $project->status == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                            <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>Zakończony</option>
                            <option value="cancelled" {{ $project->status == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Budżet (PLN)</label>
                        <input type="number" step="0.01" name="budget" value="{{ old('budget', $project->budget) }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Aktualizuj
                        </button>
                        <a href="{{ route('projects.index') }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
