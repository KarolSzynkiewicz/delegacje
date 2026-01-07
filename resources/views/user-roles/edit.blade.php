<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edytuj Rolę: {{ $userRole->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('user-roles.update', $userRole) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nazwa <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $userRole->name) }}" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:ring-2 focus:ring-blue-500">
                            @error('name')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-8">
                            <label class="block text-gray-700 text-sm font-bold mb-4">Uprawnienia</label>
                            
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 sm:p-6 max-h-[600px] overflow-y-auto">
                                @php
                                    $selectedPermissions = old('permissions', $userRole->permissions->pluck('id')->toArray());
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-blue-50 p-2 rounded transition-colors">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                                {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2">
                                            <span class="text-sm text-gray-700">{{ $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('permissions')
                                <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow-md hover:shadow-lg transition-all">
                                Zaktualizuj
                            </button>
                            <a href="{{ route('user-roles.index') }}" class="text-gray-600 hover:text-gray-900 font-medium">Anuluj</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
