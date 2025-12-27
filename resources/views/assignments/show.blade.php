<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Szczegóły Przypisania</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold">Pracownik:</dt>
                        <dd>{{ $assignment->employee->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Projekt:</dt>
                        <dd>{{ $assignment->project->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Rola:</dt>
                        <dd>{{ $assignment->role->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Status:</dt>
                        <dd>{{ ucfirst($assignment->status) }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Rozpoczęcia:</dt>
                        <dd>{{ $assignment->start_date->format('Y-m-d') }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold">Data Zakończenia:</dt>
                        <dd>{{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                    </div>
                    @if($assignment->notes)
                    <div class="md:col-span-2">
                        <dt class="font-semibold">Uwagi:</dt>
                        <dd>{{ $assignment->notes }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-6">
                    <a href="{{ route('assignments.edit', $assignment) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                    <a href="{{ route('assignments.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
