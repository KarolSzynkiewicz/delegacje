<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Zwróć Sprzęt: {{ $equipmentIssue->equipment->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-600">
                        <strong>Pracownik:</strong> {{ $equipmentIssue->employee->full_name }}<br>
                        <strong>Wydano:</strong> {{ $equipmentIssue->issue_date->format('Y-m-d') }}<br>
                        <strong>Ilość:</strong> {{ $equipmentIssue->quantity_issued }} {{ $equipmentIssue->equipment->unit }}
                    </p>
                </div>

                <form method="POST" action="{{ route('equipment-issues.return.store', $equipmentIssue) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data zwrotu *</label>
                        <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" required
                            min="{{ $equipmentIssue->issue_date->format('Y-m-d') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('return_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes', $equipmentIssue->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('equipment-issues.show', $equipmentIssue) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Zwróć Sprzęt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
