<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekt: {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('projects.edit', $project) }}"
                        routeName="projects.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                    <form action="{{ route('projects.destroy', $project) }}" 
                          method="POST" 
                          class="d-inline"
                          onsubmit="return confirm('⚠️ UWAGA: Usunięcie projektu spowoduje kaskadowe usunięcie wszystkich powiązanych danych:\n• Wszystkie przypisania pracowników\n• Wszystkie wpisy czasu pracy\n• Wszystkie zapotrzebowania\n• Wszystkie zadania\n• Wszystkie pliki\n• Wszystkie komentarze\n• Wszystkie koszty zmienne\n\nCzy na pewno chcesz usunąć ten projekt?')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" type="submit" title="Usuń projekt">
                            <i class="bi bi-trash"></i> Usuń
                        </x-ui.button>
                    </form>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-xxl">
        @if (session('success'))
            <x-ui.alert variant="success" dismissible class="mb-3">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert variant="danger" dismissible class="mb-3">
                {{ session('error') }}
            </x-ui.alert>
        @endif

        @php
            $start = $project->start_date?->copy()?->startOfDay();
            $end = $project->end_date?->copy()?->startOfDay();
            $today = now()->startOfDay();

            $totalDays = ($start && $end) ? ($start->diffInDays($end) + 1) : null;

            $effectiveEnd = $end ? $end->min($today) : $today;
            $elapsedDays = $start ? max(0, $start->diffInDays($effectiveEnd) + ($today->gte($start) ? 1 : 0)) : 0;

            if ($totalDays !== null) {
                $elapsedDays = min($elapsedDays, $totalDays);
                $progressPct = $totalDays > 0 ? (int) round(($elapsedDays / $totalDays) * 100) : 0;
                $progressPct = max(0, min(100, $progressPct));
            } else {
                $progressPct = null;
            }
        @endphp

        <div class="row mb-3">
            <div class="col-12">
                <x-ui.card label="Harmonogram">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <div class="text-muted small">Data rozpoczęcia</div>
                            <div class="fw-semibold">
                                {{ $start ? $start->format('d.m.Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Data zakończenia</div>
                            <div class="fw-semibold">
                                {{ $end ? $end->format('d.m.Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-muted small">Postęp</div>
                            <div class="fw-semibold">
                                @if ($totalDays !== null)
                                    {{ $elapsedDays }} / {{ $totalDays }} dni ({{ $progressPct }}%)
                                @elseif ($start)
                                    {{ $elapsedDays }} dni minęło
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if ($totalDays !== null)
                            <div class="progress" role="progressbar" aria-label="Postęp projektu" aria-valuenow="{{ $progressPct }}" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
                                <div class="progress-bar" style="width: {{ $progressPct }}%"></div>
                            </div>
                        @else
                            <div class="progress" role="progressbar" aria-label="Postęp projektu" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <livewire:project-tabs :project="$project" />
            </div>
        </div>
    </div>
</x-app-layout>
