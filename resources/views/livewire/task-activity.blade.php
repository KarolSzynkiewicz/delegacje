{{-- Ten sam wygląd co historia sprintu — dane tylko o tym zadaniu. --}}
@include('livewire.partials.activity-feed', [
    'entries' => $entries,
    'intro' => 'Kto i kiedy dodawał, kończył, komentował, przenosił i przypisywał to zadanie oraz jego podzadania.',
    'empty' => 'Brak zdarzeń przy tym zadaniu.',
    'keyPrefix' => 'task-act',
])
