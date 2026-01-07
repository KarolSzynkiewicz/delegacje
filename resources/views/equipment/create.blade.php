<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj Sprzęt
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('equipment.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nazwa *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Opis</label>
                        <textarea name="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategoria</label>
                        <input type="text" name="category" value="{{ old('category') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Ilość w magazynie *</label>
                            <input type="number" name="quantity_in_stock" value="{{ old('quantity_in_stock', 0) }}" min="0" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Minimalna ilość *</label>
                            <input type="number" name="min_quantity" value="{{ old('min_quantity', 0) }}" min="0" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Jednostka *</label>
                        <input type="text" name="unit" value="{{ old('unit', 'szt') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Koszt jednostkowy</label>
                        <input type="number" name="unit_cost" value="{{ old('unit_cost') }}" step="0.01" min="0"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('equipment.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
