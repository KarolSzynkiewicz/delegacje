<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Konto: {{ $employeeBankAccount->employee->full_name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ $backUrl }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('employee-bank-accounts.edit', $employeeBankAccount) }}"
                    routeName="employee-bank-accounts.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-lg-8">
            <x-ui.card label="Szczegóły konta bankowego">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <a href="{{ route('employees.show', $employeeBankAccount->employee) }}" class="text-primary text-decoration-none">
                            {{ $employeeBankAccount->employee->full_name }}
                        </a>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Numer konta:">
                        <span class="font-mono">{{ $employeeBankAccount->formattedAccountNumber() }}</span>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Data rozpoczęcia:">{{ $employeeBankAccount->start_date->format('d.m.Y') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Data zakończenia:">{{ $employeeBankAccount->end_date ? $employeeBankAccount->end_date->format('d.m.Y') : '-' }}</x-ui.detail-item>
                    <x-ui.detail-item label="Status:">
                        @php
                            if ($employeeBankAccount->isCurrentlyActive()) {
                                $statusLabel = 'Aktywne';
                                $badgeVariant = 'success';
                            } elseif ($employeeBankAccount->isPast()) {
                                $statusLabel = 'Zakończone';
                                $badgeVariant = 'accent';
                            } elseif ($employeeBankAccount->isScheduled()) {
                                $statusLabel = 'Zaplanowane';
                                $badgeVariant = 'info';
                            } else {
                                $statusLabel = 'Nieznany';
                                $badgeVariant = 'accent';
                            }
                        @endphp
                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                    </x-ui.detail-item>
                    @if($employeeBankAccount->notes)
                        <x-ui.detail-item label="Notatki:" :full-width="true">{{ $employeeBankAccount->notes }}</x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Utworzono:">{{ $employeeBankAccount->created_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                    <x-ui.detail-item label="Zaktualizowano:">{{ $employeeBankAccount->updated_at->format('d.m.Y H:i') }}</x-ui.detail-item>
                </x-ui.detail-list>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
