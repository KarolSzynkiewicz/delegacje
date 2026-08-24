{{-- Tydzień: ta sama siatka co miesiąc, ale bez limitu pasów i z nagłówkami dat w kolumnach. --}}
<div class="rc-weekscroll">
    <div class="rc-month rc-month--week">
        @foreach($weeks as $week)
            @include('livewire.partials.calendar-week-band', ['week' => $week, 'compact' => false])
        @endforeach
    </div>
</div>
