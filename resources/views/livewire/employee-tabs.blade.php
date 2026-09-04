<div class="emp-shell">
    @include('livewire.partials.employee-hero')

    <div class="emp-body">
        <nav class="card emp-rail d-none d-lg-flex" aria-label="Sekcje karty pracownika">
            @foreach($tabGroups as $groupLabel => $groupTabs)
                <div class="emp-rail__group" wire:key="emp-rail-{{ \Illuminate\Support\Str::slug($groupLabel) }}">
                    <div class="emp-rail__group-label">{{ $groupLabel }}</div>
                    @foreach($groupTabs as $tabKey => $tab)
                        <button
                            type="button"
                            class="emp-rail__item {{ $activeTab === $tabKey ? 'is-active' : '' }}"
                            wire:click="{{ $tab['wireClick'] }}"
                            wire:key="emp-rail-item-{{ $tabKey }}"
                            title="{{ $tab['label'] }}"
                        >
                            @if(! empty($tab['icon']))
                                <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                            @endif
                            <span class="emp-rail__label">{{ $tab['short'] ?? $tab['label'] }}</span>
                            @if(($tab['count'] ?? null) !== null && $tab['count'] > 0)
                                <span class="badge badge-accent ms-auto">{{ $tab['count'] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="emp-main">
            <x-ui.tabs
                :tabs="$tabsForComponent"
                :activeTab="$activeTab"
                id="employeeTabs"
                :compact-mobile="true"
                :hide-strip="true"
                mobile-label="Sekcja"
            />

            <div id="employeeTabsContent">
    @if($activeTab === 'info')
        <div id="info" role="tabpanel">
            <div class="card">
                <div class="card-body emp-dossier">
                    <div class="emp-facts">
                        <div class="emp-fact">
                            <div class="emp-fact__label">Rozmiar buta</div>
                            <p class="emp-fact__value">{{ $employee->shoe_size ?: '-' }}</p>
                        </div>
                        <div class="emp-fact">
                            <div class="emp-fact__label">Rozmiar spodni</div>
                            <p class="emp-fact__value">{{ $employee->pants_size ?: '-' }}</p>
                        </div>
                        <div class="emp-fact">
                            <div class="emp-fact__label">Konto bankowe</div>
                            <p class="emp-fact__value font-mono">{{ $currentBankAccount?->formattedAccountNumber() ?? '-' }}</p>
                        </div>
                        <div class="emp-fact">
                            <div class="emp-fact__label">Komornik</div>
                            <p class="emp-fact__value mb-0">
                                @if($employee->has_komornik)
                                    <x-ui.badge variant="warning">Tak</x-ui.badge>
                                @else
                                    <x-ui.badge variant="accent">Nie</x-ui.badge>
                                @endif
                            </p>
                        </div>
                        <div class="emp-fact emp-fact--wide">
                            <div class="emp-fact__label">Notatki</div>
                            <p class="emp-fact__value emp-fact__value--notes">{{ $employee->notes ?: '-' }}</p>
                        </div>
                    </div>
                    @if(auth()->user()->hasPermission('comments.view'))
                        <div class="emp-dossier__comments">
                            <x-comments embedded :commentable="$employee" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($activeTab === 'documents')
        <!-- Zakładka Dokumenty -->
        <div id="documents" role="tabpanel">
            <x-ui.card>
                <x-ui.table-header title="Dokumenty">
                    <x-slot name="actions">
                        <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Dokument</x-ui.button>
                    </x-slot>
                </x-ui.table-header>
                @if($tabData && $tabData->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Rodzaj</th>
                                    <th>Ważny od</th>
                                    <th>Ważny do</th>
                                    <th>Plik</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tabData as $employeeDocument)
                                    <tr>
                                        <td>{{ $employeeDocument->document->name ?? '-' }}</td>
                                        <td>
                                            <x-ui.badge variant="info">
                                                {{ $employeeDocument->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                            </x-ui.badge>
                                        </td>
                                        <td>{{ $employeeDocument->valid_from ? $employeeDocument->valid_from->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <span class="text-muted">Bezokresowy</span>
                                            @else
                                                {{ $employeeDocument->valid_to ? $employeeDocument->valid_to->format('Y-m-d') : '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($employeeDocument->file_path)
                                                <div class="d-flex gap-1">
                                                    <x-ui.button variant="ghost" href="{{ $employeeDocument->preview_url }}" target="_blank" class="btn-sm" title="Podgląd pliku">
                                                        <i class="bi bi-eye"></i>
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ $employeeDocument->file_url }}" target="_blank" class="btn-sm" title="Pobierz plik">
                                                        <i class="bi bi-download"></i>
                                                    </x-ui.button>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <x-ui.badge variant="success">Ważny</x-ui.badge>
                                            @elseif($employeeDocument->isExpired())
                                                <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                            @elseif($employeeDocument->isExpiringSoon())
                                                <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="success">Ważny</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.action-buttons>
                                                <x-ui.button variant="warning" href="{{ route('employee-documents.edit', $employeeDocument) }}" class="btn-sm">Edytuj</x-ui.button>
                                                <x-ui.delete-form 
                                                    :url="route('employee-documents.destroy', $employeeDocument)"
                                                    message="Czy na pewno chcesz usunąć ten dokument?"
                                                />
                                            </x-ui.action-buttons>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <p class="text-muted mb-3">Brak dokumentów</p>
                        <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                            Dodaj dokument
                        </x-ui.button>
                    </div>
                @endif
            </x-ui.card>
        </div>
    @elseif($activeTab === 'rotations')
        <div id="rotations" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="primary" href="{{ route('employees.rotations.create', $employee) }}" class="btn-sm">Dodaj Rotację</x-ui.button>
            </div>
            <livewire:rotations-table :employee-id="$employee->id" :wire:key="'emp-rotations-'.$employee->id" />
        </div>
    @elseif($activeTab === 'assignments')
        <div id="assignments" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="primary" href="{{ route('project-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj przypisanie</x-ui.button>
            </div>
            <livewire:assignments-table :employee-id="$employee->id" :wire:key="'emp-assignments-'.$employee->id" />
        </div>
    @elseif($activeTab === 'vehicle-assignments')
        <div id="vehicle-assignments" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="primary" href="{{ route('vehicle-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj przypisanie</x-ui.button>
            </div>
            <livewire:vehicle-assignments-table :employee-id="$employee->id" :wire:key="'emp-vehicle-assignments-'.$employee->id" />
        </div>
    @elseif($activeTab === 'accommodation-assignments')
        <div id="accommodation-assignments" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <x-ui.button variant="primary" href="{{ route('accommodation-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj przypisanie</x-ui.button>
            </div>
            <livewire:accommodation-assignments-table :employee-id="$employee->id" :wire:key="'emp-accommodation-assignments-'.$employee->id" />
        </div>
    @elseif($activeTab === 'payrolls')
        <!-- Zakładka Płace -->
        <div id="payrolls" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Płace">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('payrolls.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Payroll</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Okres</th>
                                            <th>Godziny</th>
                                            <th>Obciążenia/uznania</th>
                                            <th>Suma</th>
                                            <th>Waluta</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $payroll)
                                            <tr>
                                                <td>
                                                    {{ $payroll->period_start->format('Y-m-d') }} - {{ $payroll->period_end->format('Y-m-d') }}
                                                </td>
                                                <td>{{ number_format($payroll->hours_amount, 2, ',', ' ') }}</td>
                                                <td>{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }}</td>
                                                <td><strong>{{ number_format($payroll->total_amount, 2, ',', ' ') }}</strong></td>
                                                <td>{{ $payroll->currency }}</td>
                                                <td>
                                                    <x-ui.badge variant="info">{{ ucfirst($payroll->status->value ?? $payroll->status) }}</x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('payrolls.show', $payroll) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak płac dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'employee-rates')
        <!-- Zakładka Stawki -->
        <div id="employee-rates" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Stawki">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employee-rates.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Stawkę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Od</th>
                                            <th>Do</th>
                                            <th>Kwota</th>
                                            <th>Waluta</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $rate)
                                            <tr>
                                                <td>{{ $rate->start_date->format('Y-m-d') }}</td>
                                                <td>{{ $rate->end_date ? $rate->end_date->format('Y-m-d') : '-' }}</td>
                                                <td><strong>{{ number_format($rate->amount, 2, ',', ' ') }}</strong></td>
                                                <td>{{ $rate->currency }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-rates.show', $rate) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-rates.edit', $rate) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak stawek dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'bank')
        <div id="bank" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Konta bankowe">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employee-bank-accounts.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj konto</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Od</th>
                                            <th>Do</th>
                                            <th>Numer konta</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $account)
                                            <tr>
                                                <td>{{ $account->start_date->format('Y-m-d') }}</td>
                                                <td>{{ $account->end_date ? $account->end_date->format('Y-m-d') : '-' }}</td>
                                                <td class="font-mono">{{ $account->formattedAccountNumber() }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-bank-accounts.show', $account) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-bank-accounts.edit', $account) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak kont bankowych dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'company-assignments')
        <div id="company-assignments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Przypisania do spółek">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('company-assignments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">
                                    <i class="bi bi-plus-circle"></i> Dodaj przypisanie
                                </x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Spółka</th>
                                            <th>Okres</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $assignment)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('companies.show', $assignment->company) }}" class="text-primary">
                                                        {{ $assignment->company->name }}
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ $assignment->start_date->format('Y-m-d') }}
                                                    @if($assignment->end_date)
                                                        - {{ $assignment->end_date->format('Y-m-d') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        if ($assignment->isCurrentlyActive()) {
                                                            $statusLabel = 'Aktywne';
                                                            $badgeVariant = 'success';
                                                        } elseif ($assignment->isPast()) {
                                                            $statusLabel = 'Zakończone';
                                                            $badgeVariant = 'accent';
                                                        } elseif ($assignment->isScheduled()) {
                                                            $statusLabel = 'Zaplanowane';
                                                            $badgeVariant = 'info';
                                                        } else {
                                                            $statusLabel = 'Nieznany';
                                                            $badgeVariant = 'accent';
                                                        }
                                                    @endphp
                                                    <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('company-assignments.show', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('company-assignments.edit', $assignment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak przypisań do spółek dla tego pracownika.</p>
                            <x-ui.button variant="primary" href="{{ route('company-assignments.create', ['employee_id' => $employee->id]) }}">Dodaj pierwsze przypisanie</x-ui.button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'advances')
        <!-- Zakładka Zaliczki -->
        <div id="advances" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Zaliczki">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('advances.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Zaliczkę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Kwota</th>
                                            <th>Oprocentowanie</th>
                                            <th>Do odliczenia</th>
                                            <th>Data</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $advance)
                                            <tr>
                                                <td><strong>{{ number_format($advance->amount, 2, ',', ' ') }} {{ $advance->currency }}</strong></td>
                                                <td>
                                                    @if($advance->is_interest_bearing && $advance->interest_rate)
                                                        <x-ui.badge variant="warning">{{ number_format($advance->interest_rate, 2, ',', ' ') }}%</x-ui.badge>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td><strong class="text-danger">{{ number_format($advance->getTotalDeductionAmount(), 2, ',', ' ') }} {{ $advance->currency }}</strong></td>
                                                <td>{{ $advance->date->format('Y-m-d') }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('advances.show', $advance) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('advances.edit', $advance) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak zaliczek dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'time-logs')
        <div id="time-logs" role="tabpanel">
            @if(auth()->user()->isAdmin())
                <div class="d-flex justify-content-end mb-3">
                    <x-ui.button variant="primary" href="{{ route('time-logs.create') }}" class="btn-sm">Dodaj Wpis</x-ui.button>
                </div>
            @endif
            <livewire:time-logs-table :employee-id="$employee->id" :wire:key="'emp-time-logs-'.$employee->id" />
        </div>
    @elseif($activeTab === 'evaluations')
        <!-- Zakładka Oceny -->
        <div id="evaluations" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Oceny Pracownika">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('employee-evaluations.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj Ocenę</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Zaangażowanie</th>
                                            <th>Umiejętności</th>
                                            <th>Porządek</th>
                                            <th>Zachowanie</th>
                                            <th>Średnia</th>
                                            <th>Ocenił</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $evaluation)
                                            <tr>
                                                <td>{{ $evaluation->created_at->format('Y-m-d') }}</td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->engagement >= 7 ? 'success' : ($evaluation->engagement >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->engagement }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->skills >= 7 ? 'success' : ($evaluation->skills >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->skills }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->orderliness >= 7 ? 'success' : ($evaluation->orderliness >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->orderliness }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $evaluation->behavior >= 7 ? 'success' : ($evaluation->behavior >= 5 ? 'warning' : 'danger') }}">
                                                        {{ $evaluation->behavior }}/10
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <strong>{{ number_format($evaluation->average_score, 2, ',', ' ') }}/10</strong>
                                                </td>
                                                <td>
                                                    @if($evaluation->createdBy)
                                                        {{ $evaluation->createdBy->name }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($evaluation->notes)
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $evaluation->notes }}">
                                                            {{ Str::limit($evaluation->notes, 50) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('employee-evaluations.show', $evaluation) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    @can('update', $evaluation)
                                                        <x-ui.button variant="ghost" href="{{ route('employee-evaluations.edit', $evaluation) }}" class="btn-sm">
                                                            <i class="bi bi-pencil"></i> Edytuj
                                                        </x-ui.button>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak ocen dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'adjustments')
        <!-- Zakładka Obciążenia i uznania -->
        <div id="adjustments" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="mb-4">
                        <x-ui.table-header title="Obciążenia i uznania">
                            <x-slot name="actions">
                                <x-ui.button variant="primary" href="{{ route('adjustments.create', ['employee_id' => $employee->id]) }}" class="btn-sm">Dodaj obciążenie / uznanie</x-ui.button>
                            </x-slot>
                        </x-ui.table-header>
                        @if($tabData && $tabData->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Typ</th>
                                            <th>Kwota</th>
                                            <th>Data</th>
                                            <th>Notatki</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tabData as $adjustment)
                                            <tr>
                                                <td>
                                                    <x-ui.badge variant="{{ $adjustment->type === 'bonus' ? 'success' : 'danger' }}">
                                                        {{ $adjustment->type === 'bonus' ? 'Uznanie' : 'Obciążenie' }}
                                                    </x-ui.badge>
                                                </td>
                                                <td>
                                                    <strong class="{{ $adjustment->type === 'bonus' ? 'text-success' : 'text-danger' }}">
                                                        {{ number_format($adjustment->amount, 2, ',', ' ') }} {{ $adjustment->currency }}
                                                    </strong>
                                                </td>
                                                <td>{{ $adjustment->date->format('Y-m-d') }}</td>
                                                <td>{{ $adjustment->notes ?? '-' }}</td>
                                                <td>
                                                    <x-ui.button variant="ghost" href="{{ route('adjustments.show', $adjustment) }}" class="btn-sm">
                                                        <i class="bi bi-eye"></i> Szczegóły
                                                    </x-ui.button>
                                                    <x-ui.button variant="ghost" href="{{ route('adjustments.edit', $adjustment) }}" class="btn-sm">
                                                        <i class="bi bi-pencil"></i> Edytuj
                                                    </x-ui.button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak kar i nagród dla tego pracownika.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($activeTab === 'equipment')
        <div id="equipment" role="tabpanel">
            <livewire:employee-equipment-history :employee="$employee" :wire:key="'employee-equipment-'.$employee->id" />
        </div>
    @endif
            </div>
        </div>
    </div>

@if($showTerminateModal)
    @teleport('body')
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true"
         style="background:rgba(0,0,0,.75);z-index:2000;"
         wire:click.self="closeTerminateModal"
         wire:key="employee-terminate-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background:var(--bg-card,#1e2535);border:1px solid var(--glass-border,rgba(255,255,255,.1));color:var(--text-main,#f1f5f9);">
                <div class="modal-header" style="border-color:var(--glass-border);">
                    <h5 class="modal-title">
                        <i class="bi bi-person-x me-2 text-danger"></i>
                        Zwolnij pracownika — {{ $employee->full_name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeTerminateModal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex gap-2 align-items-start mb-3" style="font-size:.85rem;">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                        <div>
                            Ta akcja nie usuwa pracownika ani nie zmienia historii rekrutacji.
                            Zapisuje tylko datę i powód zwolnienia oraz dodaje wpis audytowy do bazy kandydatów (status: <em>Były pracownik</em>).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Powód zwolnienia *</label>
                        <select wire:model="terminationReason" class="form-select @error('terminationReason') is-invalid @enderror">
                            <option value="">— wybierz —</option>
                            @foreach(\App\Enums\EmployeeTerminationReason::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('terminationReason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label class="form-label small text-muted mb-1">Notatka (opcjonalnie)</label>
                        <textarea wire:model="terminationNote" class="form-control @error('terminationNote') is-invalid @enderror" rows="3"></textarea>
                        @error('terminationNote')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--glass-border);">
                    <button type="button" wire:click="terminate" wire:loading.attr="disabled" class="btn btn-danger">
                        <span wire:loading.remove wire:target="terminate">
                            <i class="bi bi-person-x me-1"></i>Zwolnij
                        </span>
                        <span wire:loading wire:target="terminate">
                            <i class="bi bi-hourglass-split me-1"></i>Zapisuję…
                        </span>
                    </button>
                    <button type="button" wire:click="closeTerminateModal" class="btn btn-outline-secondary">
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endteleport
@endif

{{-- Vite bywa wyłączony — krytyczny układ karty zostaje lokalnie --}}
<style>
.emp-shell { display: flex; flex-direction: column; gap: 1.15rem; }
.card.emp-hero, .emp-hero {
    display: grid !important;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 1.35rem 1.7rem;
    align-items: start;
    padding: 1.2rem 1.3rem 1.3rem !important;
    overflow: hidden;
}
.emp-hero__photo-frame {
    width: 10.5rem;
    height: 13.25rem;
    padding: 2px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    flex-shrink: 0;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.35);
}
.emp-hero--out .emp-hero__photo-frame { background: linear-gradient(135deg, #ef4444, var(--accent)); }
.emp-hero__photo {
    width: 100%; height: 100%; object-fit: cover; object-position: center top;
    border-radius: 14px; display: block; background: #0b1220;
}
.emp-hero__photo--empty {
    display: flex; align-items: center; justify-content: center;
    font-size: 2.35rem; font-weight: 700; letter-spacing: 0.06em;
    background: radial-gradient(120% 80% at 20% 0%, rgba(59,130,246,.35), transparent 55%),
        linear-gradient(165deg, rgba(59,130,246,.22), rgba(168,85,247,.18));
    color: #e2e8f0 !important;
}
.emp-hero__body { display: flex; flex-direction: column; min-width: 0; gap: 0.55rem; }
.emp-hero__bar { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem 1rem; flex-wrap: wrap; }
.emp-hero__status { display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem 0.7rem; min-width: 0; }
.emp-hero__status-meta, .emp-hero__status-note { font-size: 0.78rem; color: var(--text-muted) !important; }
.emp-hero__status-note { flex-basis: 100%; }
.emp-hero__id { font-size: 0.72rem; color: var(--text-muted) !important; letter-spacing: 0.04em; }
.emp-hero__actions { display: flex; flex-wrap: wrap; gap: 0.4rem; justify-content: flex-end; }
.emp-hero__name {
    font-size: clamp(1.45rem, 2.2vw, 2.05rem); font-weight: 700;
    letter-spacing: -0.03em; line-height: 1.12; margin: 0.15rem 0 0;
    color: var(--text-main) !important;
}
.emp-hero__roles { display: flex; flex-wrap: wrap; gap: 0.35rem; }
.emp-hero__contact { display: flex; flex-wrap: wrap; gap: 0.45rem 1.15rem; margin-top: 0.25rem; }
.emp-hero__contact-row {
    display: inline-flex; align-items: center; gap: 0.5rem; min-width: 0; max-width: 100%;
    font-size: 0.88rem; color: var(--text-muted) !important; text-decoration: none;
}
.emp-hero__contact-row:hover { color: var(--text-main) !important; }
.emp-hero__contact-icon {
    width: 1.7rem; height: 1.7rem; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
    background: rgba(59,130,246,.14); border: 1px solid rgba(59,130,246,.22);
    color: #93c5fd !important; font-size: 0.72rem;
}
.emp-hero__contact-icon i { color: inherit !important; }
.emp-body { display: grid; grid-template-columns: 1fr; gap: 1.15rem; align-items: start; }
.emp-main { min-width: 0; }
.emp-rail {
    flex-direction: column; gap: 0.95rem; padding: 0.8rem 0.65rem !important;
    position: sticky; top: 1rem; align-self: start;
}
.emp-rail__group { display: flex; flex-direction: column; gap: 0.12rem; }
.emp-rail__group-label {
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
    color: var(--text-muted) !important; padding: 0 0.55rem 0.3rem;
}
.emp-rail__item {
    position: relative; appearance: none; display: flex; align-items: center; gap: 0.5rem;
    width: 100%; padding: 0.4rem 0.55rem 0.4rem 0.7rem; border: 0; border-radius: 8px;
    background: transparent; color: var(--text-muted) !important;
    font-size: 0.84rem; font-weight: 600; line-height: 1.2; text-align: left;
    font-family: inherit; cursor: pointer;
}
.emp-rail__item i { font-size: 0.95rem; color: inherit !important; width: 1.05rem; text-align: center; flex-shrink: 0; }
.emp-rail__label { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.emp-rail__item:hover { color: var(--text-main) !important; background: rgba(255,255,255,.04); }
.emp-rail__item.is-active { color: var(--text-main) !important; background: rgba(59,130,246,.1); }
.emp-rail__item.is-active::before {
    content: ''; position: absolute; left: 0; top: 7px; bottom: 7px; width: 2px; border-radius: 2px;
    background: linear-gradient(180deg, var(--primary), var(--accent));
}
.emp-facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); gap: 0.8rem; }
.emp-fact {
    padding: 0.85rem 1rem; border: 1px solid var(--glass-border); border-radius: 12px;
    background: rgba(255,255,255,.025); min-width: 0;
}
.emp-fact--wide { grid-column: 1 / -1; }
.emp-fact__label {
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--text-muted) !important; margin-bottom: 0.35rem;
}
.emp-fact__value { font-size: 0.95rem; font-weight: 600; color: var(--text-main) !important; margin: 0; overflow-wrap: anywhere; }
.emp-fact__value--notes { font-weight: 500; line-height: 1.45; color: #cbd5e1 !important; }
.emp-dossier { padding: 0.35rem 0.15rem !important; }
.emp-dossier__comments { margin-top: 1.15rem; padding-top: 0.95rem; border-top: 1px solid var(--glass-border); }
.ui-compact-nav__group {
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted) !important; padding: 0.55rem 0.7rem 0.2rem; list-style: none;
}
@media (min-width: 992px) {
    .emp-body { grid-template-columns: 13.75rem minmax(0, 1fr); }
    .emp-hero { grid-template-columns: 11.25rem minmax(0, 1fr); }
    .emp-hero__photo-frame { width: 11.25rem; height: 14.15rem; }
}
@media (max-width: 575.98px) {
    .emp-hero { grid-template-columns: 5.5rem minmax(0, 1fr); gap: 0.85rem 0.95rem; padding: 1rem !important; }
    .emp-hero__photo-frame { width: 5.5rem; height: 7rem; border-radius: 12px; }
    .emp-hero__photo, .emp-hero__photo--empty { border-radius: 10px; }
    .emp-hero__photo--empty { font-size: 1.35rem; }
    .emp-hero__name { font-size: 1.28rem; }
    .emp-hero__actions { width: 100%; justify-content: flex-start; }
    .emp-facts { grid-template-columns: 1fr; }
}
</style>
</div>
