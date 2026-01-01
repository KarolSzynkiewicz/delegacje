<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pracownicy</h2>
            <a href="{{ route('employees.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Pracownika</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zdjęcie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imię i Nazwisko</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zasoby</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-6 py-4">
                                    @if($employee->image_path)
                                        <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" class="rounded-full" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="rounded-full bg-gray-300 flex items-center justify-center" style="width: 50px; height: 50px;">
                                            <span class="text-gray-600 text-sm">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $employee->full_name }}</td>
                                <td class="px-6 py-4">
                                    @if($employee->roles->count() > 0)
                                        @foreach($employee->roles as $role)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">{{ $role->name }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('employees.vehicles.index', $employee) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Pojazdy</a>
                                    <a href="{{ route('employees.accommodations.index', $employee) }}" class="text-red-600 hover:text-red-900">Mieszkania</a>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Brak pracowników</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
