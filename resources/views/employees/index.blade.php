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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imię i Nazwisko</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-6 py-4">{{ $employee->full_name }}</td>
                                <td class="px-6 py-4">{{ $employee->role->name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $employee->email }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak pracowników</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
