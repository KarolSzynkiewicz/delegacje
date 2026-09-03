<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dashboard" />
    </x-slot>

    <x-ui.card class="mb-5">
        <div class="text-center py-4 py-md-5">
            <h1 class="h2 fw-bold mb-3">ChronoLogic w skrócie</h1>
            <p class="lead text-muted mb-2">ERP do delegacji: kto gdzie jest, ile godzin, ile kosztuje, kto jedzie.</p>
            <p class="text-muted mb-0">Poniżej <strong>snapy</strong> prawdziwych ekranów z przykładowymi danymi — tak wygląda praca w systemie, zanim wrzucisz swoje ekipy.</p>
        </div>
    </x-ui.card>

    <nav class="dash-snap-toc mb-5" aria-label="Snapy systemu">
        <a href="#boty">Boty</a>
        <a href="#snap-weekly">Tydzień</a>
        <a href="#snap-charts">Wykresy</a>
        <a href="#snap-hours">Godziny</a>
        <a href="#snap-payroll">Payroll</a>
        <a href="#snap-people">Kadry</a>
        <a href="#snap-finance">Finanse</a>
        <a href="#snap-warehouse">Magazyn</a>
        <a href="#snap-hr">Rekrutacja</a>
        <a href="#snap-tasks">Sprint</a>
    </nav>

    <section class="mb-5" id="boty" aria-labelledby="dash-bots-title">
        <div class="dash-bots__head">
            <span class="dash-bots__kicker">AskChrono</span>
            <h2 id="dash-bots-title" class="h4 fw-semibold mb-1">Poznaj boty — Twoi agenci AI</h2>
            <p class="text-muted mb-0 small">Czterech specjalistów obok Twojej pracy. Każdy robi jedną rzecz — i nic nie zapisuje, dopóki nie zatwierdzisz.</p>
        </div>
        <div class="dash-bots__grid">
            @foreach (\App\Support\ChronoPersona::all() as $bot)
                <x-ui.card class="dash-bot">
                    <div class="dash-bot__figure">
                        <x-ask-chrono-bot :variant="$bot['variant']" :size="64" />
                    </div>
                    <span class="dash-bot__role font-mono">{{ $bot['role'] }}</span>
                    <h3>{{ $bot['name'] }}</h3>
                    <p>{{ $bot['pitch'] }}</p>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    <div class="dash-snaps" id="snap-weekly">
        @include('dashboard.snaps.weekly')
    </div>
    <div class="dash-snaps" id="snap-charts">
        @include('dashboard.snaps.charts')
    </div>
    <div class="dash-snaps" id="snap-hours">
        @include('dashboard.snaps.time-logs')
    </div>
    <div class="dash-snaps" id="snap-payroll">
        @include('dashboard.snaps.payroll')
    </div>
    <div class="dash-snaps" id="snap-people">
        @include('dashboard.snaps.employees')
    </div>
    <div class="dash-snaps" id="snap-finance">
        @include('dashboard.snaps.finance')
    </div>
    <div class="dash-snaps" id="snap-warehouse">
        @include('dashboard.snaps.warehouse')
    </div>
    <div class="dash-snaps" id="snap-hr">
        @include('dashboard.snaps.recruitment')
    </div>
    <div class="dash-snaps" id="snap-tasks">
        @include('dashboard.snaps.tasks-sprint')
    </div>
</x-app-layout>
