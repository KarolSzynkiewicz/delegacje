<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dodaj Przypisanie Pracownika do Projektu</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div x-data="{ 
                    employeeId: '', 
                    startDate: @js(old('start_date', $startDate ?? '')), 
                    endDate: @js(old('end_date', $endDate ?? '')),
                    showAvailabilityInfo() {
                        const select = document.querySelector('select[name=\'employee_id\']');
                        const selectedOption = select.options[select.selectedIndex];
                        const infoDiv = document.getElementById('availability-info');
                        const reasonsUl = document.getElementById('availability-reasons');
                        
                        if (selectedOption && selectedOption.dataset.available === '0') {
                            const reasons = JSON.parse(selectedOption.dataset.reasons || '[]');
                            reasonsUl.innerHTML = '';
                            reasons.forEach(reason => {
                                const li = document.createElement('li');
                                li.textContent = reason;
                                reasonsUl.appendChild(li);
                            });
                            infoDiv.style.display = 'block';
                        } else {
                            infoDiv.style.display = 'none';
                        }
                    }
                }">
                <form method="POST" action="{{ route('projects.assignments.store', $project) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Projekt</label>
                        <input type="text" value="{{ $project->name }}" disabled
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik</label>
                        <select name="employee_id" 
                                x-model="employeeId" 
                                @change="showAvailabilityInfo()"
                                required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                @php
                                    $isAvailable = $employee->availability_status['available'] ?? true;
                                    $reasons = $employee->availability_status['reasons'] ?? [];
                                @endphp
                                @php
                                    $optionText = $employee->full_name;
                                    if ($employee->roles->count() > 0) {
                                        $optionText .= ' (' . $employee->roles->pluck('name')->join(', ') . ')';
                                    }
                                    if (!$isAvailable && !empty($reasons)) {
                                        // Skróć powody do krótkich komunikatów
                                        $shortReasons = [];
                                        foreach ($reasons as $reason) {
                                            if (str_contains($reason, 'dokument')) {
                                                $shortReasons[] = 'Brak dok';
                                            } elseif (str_contains($reason, 'rotacji')) {
                                                $shortReasons[] = 'Brak rotacji';
                                            } elseif (str_contains($reason, 'przypisany') || str_contains($reason, 'projekcie')) {
                                                $shortReasons[] = 'W innym projekcie';
                                            } else {
                                                $shortReasons[] = $reason;
                                            }
                                        }
                                        $optionText .= ' - ' . implode(', ', $shortReasons);
                                    }
                                @endphp
                                <option value="{{ $employee->id }}" 
                                        {{ old('employee_id') == $employee->id ? 'selected' : '' }}
                                        {{ !$isAvailable ? 'disabled' : '' }}
                                        data-available="{{ $isAvailable ? '1' : '0' }}"
                                        data-reasons="{{ json_encode($reasons) }}"
                                        style="{{ !$isAvailable ? 'color: #9ca3af; background-color: #f3f4f6;' : '' }}">
                                    {{ $optionText }}
                                </option>
                            @endforeach
                        </select>
                        <style>
                            select[name="employee_id"] option:disabled {
                                color: #9ca3af !important;
                                background-color: #f3f4f6 !important;
                                font-style: italic;
                            }
                        </style>
                        @error('employee_id')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                        
                        <!-- Informacja o niedostępności -->
                        @if($startDate && $endDate)
                            <div id="availability-info" class="mt-2 text-sm" style="display: none;">
                                <div class="bg-gray-100 border border-gray-300 rounded p-3">
                                    <p class="font-semibold text-gray-700 mb-1">Pracownik niedostępny:</p>
                                    <ul id="availability-reasons" class="list-disc list-inside text-gray-600">
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Rola w Projekcie</label>
                        <select name="role_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz rolę</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Rozpoczęcia</label>
                        <input type="date" 
                               name="start_date" 
                               x-model="startDate"
                               :value="startDate"
                               required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('start_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Zakończenia (opcjonalnie)</label>
                        <input type="date" 
                               name="end_date" 
                               x-model="endDate"
                               :value="endDate"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="pending">Oczekujące</option>
                            <option value="active" selected>Aktywne</option>
                            <option value="completed">Zakończone</option>
                            <option value="cancelled">Anulowane</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Uwagi</label>
                        <textarea name="notes" rows="3"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                        <a href="{{ route('projects.assignments.index', $project) }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                                    <livewire:employee-availability-checker 
                        x-bind:employee-id="employeeId" 
                        x-bind:start-date="startDate" 
                        x-bind:end-date="endDate" 
                    />
                </form>
            </div>
            </div>
        </div>
    </div>
</x-app-layout>
