<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dodaj zapotrzebowanie dla projektu: {{ $project->name }}
            </h2>
            <a href="{{ route('projects.demands.index', $project) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('projects.demands.store', $project) }}" method="POST" id="demands-form">
                    @csrf
                    
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

                    @if($dateFrom && $dateTo)
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Zakres dat:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d.m.Y') }}
                        </p>
                    </div>
                    @endif

                    <!-- Wspólne daty dla wszystkich ról -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data od <span class="text-red-500">*</span></label>
                            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do (opcjonalnie)</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Tabela z wszystkimi rolami -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Zapotrzebowanie na role:</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rola</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ilość osób</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($roles as $index => $role)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <label class="text-sm font-medium text-gray-900">{{ $role->name }}</label>
                                            @if($role->description)
                                                <p class="text-xs text-gray-500 mt-1">{{ $role->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $existingDemand = $existingDemands[$role->id] ?? null;
                                                $currentValue = $existingDemand ? $existingDemand->required_count : 0;
                                            @endphp
                                            <input 
                                                type="number" 
                                                name="demands[{{ $role->id }}][required_count]" 
                                                min="0" 
                                                value="{{ old("demands.{$role->id}.required_count", $currentValue) }}" 
                                                step="1"
                                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 demand-count-input"
                                                data-role-id="{{ $role->id }}"
                                            >
                                            <input type="hidden" name="demands[{{ $role->id }}][role_id]" value="{{ $role->id }}">
                                            @if($existingDemand)
                                                <p class="text-xs text-gray-500 mt-1">Istniejące: {{ $existingDemand->required_count }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Uwagi -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uwagi (opcjonalnie)</label>
                        <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end items-center mt-6">
                        <a href="{{ route('projects.demands.index', $project) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz zapotrzebowania
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Podświetl wiersze z ilością > 0
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.demand-count-input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    if (parseInt(this.value) > 0) {
                        row.classList.add('bg-green-50');
                    } else {
                        row.classList.remove('bg-green-50');
                    }
                });
            });
        });
    </script>
</x-app-layout>

