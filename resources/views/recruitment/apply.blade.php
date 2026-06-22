<x-layouts.public :title="'Formularz rekrutacyjny – ' . config('app.name')">

    <div class="min-vh-100 d-flex flex-column">

        {{-- Nagłówek publiczny --}}
        <header class="py-3 px-4 border-bottom" style="background: var(--bg-card); border-color: var(--border-color) !important;">
            <div class="container-md d-flex align-items-center gap-2">
                <i class="bi bi-briefcase-fill text-primary fs-5"></i>
                <span class="fw-semibold fs-5">{{ config('app.name') }}</span>
                <span class="text-muted small ms-2">— Rekrutacja</span>
            </div>
        </header>

        {{-- Treść --}}
        <main class="flex-grow-1 py-5 px-3">
            <div class="container-md" style="max-width:700px;">

                <div class="mb-5 text-center">
                    <h1 class="fw-semibold fs-3 mb-2">Formularz zgłoszeniowy</h1>
                    <p class="text-muted">
                        Wypełnij poniższy formularz, aby złożyć swoją kandydaturę.
                        Skontaktujemy się z Tobą jak najszybciej.
                    </p>
                </div>

                <div class="card p-4 p-md-5">
                    <livewire:recruitment-form />
                </div>

            </div>
        </main>

        {{-- Stopka --}}
        <footer class="py-3 text-center" style="border-top: 1px solid var(--border-color);">
            <small class="text-muted">
                &copy; {{ date('Y') }} {{ config('app.name') }} &mdash;
                <a href="{{ route('recruitment.rodo') }}" class="text-muted">RODO</a>
                &middot;
                <a href="{{ route('recruitment.consent.recruitment') }}" class="text-muted">Zgoda rekrutacyjna</a>
                &middot;
                <a href="{{ route('recruitment.consent.marketing') }}" class="text-muted">Zgoda marketingowa</a>
            </small>
        </footer>

    </div>

</x-layouts.public>
