<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lokalizacja: {{ $location->name }}</h2>
            <div>
                <a href="{{ route('locations.edit', $location) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                <a href="{{ route('locations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Nazwa</h3>
                        <p class="text-gray-900">{{ $location->name }}</p>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Adres</h3>
                        <p class="text-gray-900">{{ $location->address }}</p>
                    </div>
                    @if($location->city)
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Miasto</h3>
                        <p class="text-gray-900">{{ $location->city }}</p>
                    </div>
                    @endif
                    @if($location->postal_code)
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Kod pocztowy</h3>
                        <p class="text-gray-900">{{ $location->postal_code }}</p>
                    </div>
                    @endif
                    @if($location->contact_person)
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Osoba kontaktowa</h3>
                        <p class="text-gray-900">{{ $location->contact_person }}</p>
                    </div>
                    @endif
                    @if($location->phone)
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Telefon</h3>
                        <p class="text-gray-900">{{ $location->phone }}</p>
                    </div>
                    @endif
                    @if($location->email)
                    <div>
                        <h3 class="font-bold text-gray-700 mb-2">Email</h3>
                        <p class="text-gray-900">{{ $location->email }}</p>
                    </div>
                    @endif
                    @if($location->description)
                    <div class="col-span-2">
                        <h3 class="font-bold text-gray-700 mb-2">Opis</h3>
                        <p class="text-gray-900">{{ $location->description }}</p>
                    </div>
                    @endif
                </div>

                @if($location->projects->count() > 0)
                <div class="mt-6">
                    <h3 class="font-bold text-gray-700 mb-2">Projekty w tej lokalizacji ({{ $location->projects->count() }})</h3>
                    <ul class="list-disc list-inside">
                        @foreach($location->projects as $project)
                            <li><a href="{{ route('projects.show', $project) }}" class="text-blue-600 hover:text-blue-900">{{ $project->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

