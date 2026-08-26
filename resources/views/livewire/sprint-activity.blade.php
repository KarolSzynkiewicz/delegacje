{{-- Historia całego sprintu (wiele zadań). --}}
@include('livewire.partials.activity-feed', [
    'entries' => $entries,
    'intro' => 'Kto i kiedy dodawał, kończył, komentował, przenosił i przypisywał zadania oraz podzadania w tym sprincie.',
    'empty' => 'Brak zdarzeń w tym sprincie.',
    'keyPrefix' => 'sprint-act',
])
