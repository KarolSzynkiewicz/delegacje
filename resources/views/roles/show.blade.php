<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rola: {{ $role->name }}</h2>
            <div>
                <a href="{{ route('roles.edit', $role) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                <a href="{{ route('roles.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Nazwa</h3>
                        <p class="text-gray-900">{{ $role->name }}</p>
                    </div>
                    @if($role->description)
                    <div class="col-span-2">
                        <h3 class="font-bold text-gray-700 mb-2">Opis</h3>
                        <p class="text-gray-900">{{ $role->description }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if($role->employees->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-bold text-gray-700 mb-4">Pracownicy z tą rolą ({{ $role->employees->count() }})</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imię i Nazwisko</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefon</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($role->employees as $employee)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">{{ $employee->email }}</td>
                                <td class="px-6 py-4">{{ $employee->phone ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($role->projectDemands->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-bold text-gray-700 mb-4">Zapotrzebowania na tę rolę ({{ $role->projectDemands->count() }})</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projekt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liczba osób</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Okres</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($role->projectDemands as $demand)
                            <tr>
                                <td class="px-6 py-4">
                                    <a href="{{ route('projects.show', $demand->project) }}" class="text-blue-600 hover:text-blue-900">
                                        {{ $demand->project->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">{{ $demand->required_count }}</td>
                                <td class="px-6 py-4">
                                    {{ $demand->date_from->format('Y-m-d') }}
                                    @if($demand->date_to)
                                        - {{ $demand->date_to->format('Y-m-d') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

