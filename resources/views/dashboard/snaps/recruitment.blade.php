@php
    $r = $snaps['recruitment'];
    $proc = $r['process'];
    $selectedId = $proc['candidate']->processes->first()->id;
@endphp

<x-dashboard.snap
    kicker="Rekrutacja"
    title="Kandydaci i proces"
    caption="Lista kandydatów z procesami (status pipeline) oraz karta konkretnego procesu: od Nowy do Zatrudniony."
    :href="Route::has('recruitment-processes.index') ? route('recruitment-processes.index') : null"
    tall
>
    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.card label="Kandydaci">
                @foreach($r['candidates'] as $cand)
                    @include('livewire.partials.rp-candidate-group', [
                        'cand' => $cand,
                        'selectedId' => $selectedId,
                        'status' => null,
                        'isPinned' => $cand->id === $proc['candidate']->id,
                        'readonly' => true,
                    ])
                @endforeach
            </x-ui.card>
        </div>
        <div class="col-lg-7">
            <x-ui.card label="Proces #{{ $selectedId }}">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <x-ui.avatar :initials="mb_strtoupper(mb_substr($proc['candidate']->first_name,0,1).mb_substr($proc['candidate']->last_name,0,1))" size="40px" shape="rounded" :border="false" />
                    <div>
                        <div class="fw-semibold">{{ $proc['candidate']->full_name }}</div>
                        <div class="small text-muted">{{ $proc['candidate']->phone }} · {{ $proc['role'] }}</div>
                    </div>
                    <x-ui.badge class="ms-auto" :variant="$proc['status']->variant()">{{ $proc['status']->label() }}</x-ui.badge>
                </div>
                <p class="small text-muted mb-3">Źródło: {{ $proc['source'] }}</p>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach($r['pipeline'] as $step)
                        <x-ui.badge :variant="$step === $proc['status'] ? $step->variant() : 'secondary'">
                            {{ $step->label() }}
                        </x-ui.badge>
                    @endforeach
                </div>
                <x-ui.alert variant="info" class="mb-0">
                    <strong>Następny krok:</strong> {{ $proc['next'] }}
                </x-ui.alert>
            </x-ui.card>
        </div>
    </div>
</x-dashboard.snap>
