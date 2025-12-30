<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dodaj Nową Lokalizację</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('locations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nazwa <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        @error('name')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Adres <span class="text-red-500">*</span></label>
                        <input type="text" name="address" value="{{ old('address') }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        @error('address')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Miasto</label>
                        <input type="text" name="city" value="{{ old('city') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kod pocztowy</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Osoba kontaktowa</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Telefon</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('email')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Opis</label>
                        <textarea name="description" rows="4"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                        <a href="{{ route('locations.index') }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

