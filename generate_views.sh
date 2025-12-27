#!/bin/bash

# Script to generate all necessary Blade views for the logistics system

BASE_DIR="/home/ubuntu/delegacje/resources/views"

# Create projects/index.blade.php
cat > "$BASE_DIR/projects/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Projekty</h2>
            <a href="{{ route('projects.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Projekt</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($projects as $project)
                            <tr>
                                <td class="px-6 py-4">{{ $project->name }}</td>
                                <td class="px-6 py-4">{{ $project->client_name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('projects.edit', $project) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edytuj</a>
                                    <a href="{{ route('demands.create', ['project_id' => $project->id]) }}" class="text-green-600 hover:text-green-900">Zapotrzebowanie</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak projektów</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# Create projects/create.blade.php
cat > "$BASE_DIR/projects/create.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dodaj Nowy Projekt</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('projects.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nazwa Projektu</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        @error('name')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Klient</label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Opis</label>
                        <textarea name="description" rows="4"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="active">Aktywny</option>
                            <option value="on_hold">Wstrzymany</option>
                            <option value="completed">Zakończony</option>
                            <option value="cancelled">Anulowany</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Budżet (PLN)</label>
                        <input type="number" step="0.01" name="budget" value="{{ old('budget') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                        <a href="{{ route('projects.index') }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# Create projects/show.blade.php
cat > "$BASE_DIR/projects/show.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Szczegóły Projektu: {{ $project->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold">Nazwa:</dt>
                        <dd>{{ $project->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Klient:</dt>
                        <dd>{{ $project->client_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Status:</dt>
                        <dd>{{ ucfirst($project->status) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Budżet:</dt>
                        <dd>{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</dd>
                    </div>
                    <div class="md:col-span-2">
                        <dt class="font-semibold">Opis:</dt>
                        <dd>{{ $project->description ?? '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <a href="{{ route('projects.edit', $project) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                    <a href="{{ route('projects.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
                </div>
            </div>

            @if($project->demand)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Zapotrzebowanie</h3>
                <p>Liczba pracowników: {{ $project->demand->required_workers_count }}</p>
                <p>Od: {{ $project->demand->start_date->format('Y-m-d') }}</p>
                <p>Do: {{ $project->demand->end_date ? $project->demand->end_date->format('Y-m-d') : 'Nieokreślone' }}</p>
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Przypisani Pracownicy</h3>
                @if($project->assignments->count() > 0)
                    <ul>
                        @foreach($project->assignments as $assignment)
                            <li class="mb-2">
                                {{ $assignment->employee->full_name }} - {{ $assignment->role->name }}
                                ({{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }})
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">Brak przypisanych pracowników</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# Create projects/edit.blade.php
cat > "$BASE_DIR/projects/edit.blade.php" << 'EOF'
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
EOF

echo "Widoki dla projektów zostały utworzone!"

# Create assignments/index.blade.php
cat > "$BASE_DIR/assignments/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypisania Pracowników do Projektów</h2>
            <a href="{{ route('assignments.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Przypisanie</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projekt</th>
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
                                <td class="px-6 py-4">{{ $assignment->project->name }}</td>
                                <td class="px-6 py-4">{{ $assignment->role->name }}</td>
                                <td class="px-6 py-4">
                                    {{ $assignment->start_date->format('Y-m-d') }} - 
                                    {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('assignments.show', $assignment) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('assignments.edit', $assignment) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
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
</x-app-layout>
EOF

echo "Widoki dla przypisań zostały utworzone!"
echo "Wszystkie widoki zostały wygenerowane!"
EOF

chmod +x /home/ubuntu/delegacje/generate_views.sh
