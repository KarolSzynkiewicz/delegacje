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
