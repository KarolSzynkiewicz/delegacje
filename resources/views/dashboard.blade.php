<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Witaj w systemie zarządzania logistyką!</h3>
                    <p class="mb-6">System Stocznia - zarządzanie projektami, pracownikami i zasobami.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Widok Tygodniowy -->
                        <a href="{{ route('weekly-overview.index') }}" class="block p-6 bg-gradient-to-br from-teal-50 to-cyan-50 border-2 border-teal-300 rounded-lg hover:from-teal-100 hover:to-cyan-100 transition shadow-lg">
                            <div class="flex items-center mb-2">
                                <svg class="w-8 h-8 text-teal-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <h4 class="text-lg font-bold text-teal-800">Przegląd Tygodniowy</h4>
                            </div>
                            <p class="text-sm text-teal-700 font-medium">Tygodniowy podgląd przydziałów ekip</p>
                            <p class="text-xs text-teal-600 mt-2">Projekty • Pracownicy • Domy • Auta</p>
                        </a>

                        <!-- Projekty -->
                        <a href="{{ route('projects.index') }}" class="block p-6 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-blue-800">Projekty</h4>
                            </div>
                            <p class="text-sm text-blue-700">Zarządzaj projektami i zapotrzebowaniem</p>
                        </a>

                        <!-- Przypisania -->
                        <a href="{{ route('assignments.index') }}" class="block p-6 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-green-800">Przypisania</h4>
                            </div>
                            <p class="text-sm text-green-700">Przypisania pracowników do projektów</p>
                        </a>

                        <!-- Pracownicy -->
                        <a href="{{ route('employees.index') }}" class="block p-6 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-purple-800">Pracownicy</h4>
                            </div>
                            <p class="text-sm text-purple-700">Zarządzaj bazą pracowników</p>
                        </a>

                        <!-- Pojazdy -->
                        <a href="{{ route('vehicles.index') }}" class="block p-6 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-yellow-800">Pojazdy</h4>
                            </div>
                            <p class="text-sm text-yellow-700">Zarządzaj flotą pojazdów</p>
                        </a>

                        <!-- Mieszkania -->
                        <a href="{{ route('accommodations.index') }}" class="block p-6 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-red-800">Mieszkania</h4>
                            </div>
                            <p class="text-sm text-red-700">Zarządzaj akomodacjami</p>
                        </a>

                        <!-- Przypisania Pojazdów -->
                        <a href="{{ route('vehicle-assignments.index') }}" class="block p-6 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-indigo-800">Przypisania Pojazdów</h4>
                            </div>
                            <p class="text-sm text-indigo-700">Przypisania pracownik-pojazd</p>
                        </a>

                        <!-- Przypisania Mieszkań -->
                        <a href="{{ route('accommodation-assignments.index') }}" class="block p-6 bg-pink-50 border border-pink-200 rounded-lg hover:bg-pink-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-pink-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-pink-800">Przypisania Mieszkań</h4>
                            </div>
                            <p class="text-sm text-pink-700">Przypisania pracownik-mieszkanie</p>
                        </a>

                        <!-- Lokalizacje -->
                        <a href="{{ route('locations.index') }}" class="block p-6 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-emerald-800">Lokalizacje</h4>
                            </div>
                            <p class="text-sm text-emerald-700">Zarządzaj lokalizacjami projektów</p>
                        </a>

                        <!-- Role -->
                        <a href="{{ route('roles.index') }}" class="block p-6 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-amber-800">Role</h4>
                            </div>
                            <p class="text-sm text-amber-700">Zarządzaj rolami w projektach</p>
                        </a>

                        <!-- Role Użytkowników -->
                        <a href="{{ route('user-roles.index') }}" class="block p-6 bg-violet-50 border border-violet-200 rounded-lg hover:bg-violet-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-violet-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-violet-800">Role Użytkowników</h4>
                            </div>
                            <p class="text-sm text-violet-700">Zarządzaj rolami i uprawnieniami</p>
                        </a>

                        <!-- Wymagania formalne -->
                        <a href="{{ route('documents.index') }}" class="block p-6 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-blue-800">Wymagania formalne</h4>
                            </div>
                            <p class="text-sm text-blue-700">Zarządzaj wymaganiami formalnymi</p>
                        </a>

                        <!-- Dokumenty Pracowników -->
                        <a href="{{ route('employee-documents.index') }}" class="block p-6 bg-orange-50 border border-orange-200 rounded-lg hover:bg-orange-100 transition">
                            <div class="flex items-center mb-2">
                                <svg class="w-6 h-6 text-orange-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h4 class="text-lg font-semibold text-orange-800">Dokumenty Pracowników</h4>
                            </div>
                            <p class="text-sm text-orange-700">Zarządzaj dokumentami pracowników</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
