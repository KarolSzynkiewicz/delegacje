<x-guest-layout>
    <div class="cl-landing">
        <x-landing.nav>
            <a href="#moduly">Moduły</a>
            <a href="#jak-dziala">Jak działa</a>
            <span class="cl-landing-clock font-mono"><span class="cl-landing-clock__dot"></span><span data-cl-clock>00:00:00</span></span>
        </x-landing.nav>

        <section class="cl-landing-hero">
            <div class="cl-landing-wrap">
                <span class="cl-landing-eyebrow">ERP dla firm zatrudniających za granicą</span>
                <h1>
                    Rotacje pracowników.<br>
                    Pod kontrolą, <em>nie w Excelu.</em>
                </h1>
                <p class="cl-landing-sub">
                    ChronoLogic prowadzi pracownika od pierwszego kontaktu, przez wyjazd, zakwaterowanie i pracę na budowie, aż po wypłatę — w jednym systemie.
                </p>
                <div class="cl-landing-hero__cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">Zaloguj się</a>
                    <a href="#moduly" class="btn btn-outline-secondary">Zobacz jak to działa</a>
                </div>

                <ol class="cl-landing-timeline">
                    <li>Rekrutacja</li>
                    <li>Wyjazd</li>
                    <li>Kwatera</li>
                    <li>Projekt</li>
                    <li>Płace</li>
                </ol>
            </div>
        </section>

        <div class="cl-landing-marquee" aria-hidden="true">
            <div class="cl-landing-marquee__track">
                @foreach ([0, 1] as $copy)
                    <span>POLSKA</span><span>FRANCJA</span><span>ESTONIA</span><span>HISZPANIA</span>
                    <span>LOGISTYKA</span><span>REKRUTACJA</span><span>PŁACE</span><span>FLOTA</span>
                @endforeach
            </div>
        </div>

        <section class="cl-landing-section" id="problem">
            <div class="cl-landing-wrap">
                <div class="cl-landing-section__head">
                    <span class="cl-landing-kicker">Zanim powstał ChronoLogic</span>
                    <h2>Ten sam pracownik żył w trzech miejscach naraz.</h2>
                    <p>W arkuszu, na czacie i na kartce w segregatorze — a żadne z nich nie wiedziało o pozostałych.</p>
                </div>
                <div class="cl-landing-grid cl-landing-grid--3">
                    <article class="cl-landing-tile">
                        <span class="cl-landing-tile__tag font-mono">Arkusz 47_final_v2.xlsx</span>
                        <h3>Excel</h3>
                        <p>Rotacje, adresy, terminy wiz — wszystko w jednym pliku, edytowanym przez pięć osób naraz.</p>
                    </article>
                    <article class="cl-landing-tile">
                        <span class="cl-landing-tile__tag font-mono">17 nieprzeczytanych</span>
                        <h3>WhatsApp</h3>
                        <p>Zmiana godziny transferu ustalana w wątku, którego nikt później nie odnajdzie.</p>
                    </article>
                    <article class="cl-landing-tile">
                        <span class="cl-landing-tile__tag font-mono">Segregator 03/2024</span>
                        <h3>Papier</h3>
                        <p>Umowa podpisana, zeskanowana, wysłana mailem — i zgubiona po drodze.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="cl-landing-section" id="moduly">
            <div class="cl-landing-wrap">
                <div class="cl-landing-section__head">
                    <span class="cl-landing-kicker">Jeden system, sześć obszarów</span>
                    <h2>Wszystko, co dotyczy pracownika, w jednym miejscu.</h2>
                </div>
                <div class="cl-landing-grid cl-landing-grid--3">
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">01</span>
                        <h3>Logistyka</h3>
                        <p>Transfery, trasy i wydarzenia wyjazdowe — kto, skąd, dokąd i kiedy.</p>
                    </article>
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">02</span>
                        <h3>Rekrutacja</h3>
                        <p>Leady, procesy i kandydaci w jednym lejku — od pierwszego kontaktu do zatrudnienia.</p>
                    </article>
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">03</span>
                        <h3>Zakwaterowanie</h3>
                        <p>Kwatery, dostępność miejsc i przypisania — bez podwójnych rezerwacji.</p>
                    </article>
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">04</span>
                        <h3>Flota</h3>
                        <p>Pojazdy, przeglądy i przypisania kierowców widoczne dla całego zespołu.</p>
                    </article>
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">05</span>
                        <h3>Kadry i płace</h3>
                        <p>Dane pracownika i rozliczenia w jednym rekordzie, nie w kolejnym arkuszu.</p>
                    </article>
                    <article class="cl-landing-module">
                        <span class="cl-landing-module__num font-mono">06</span>
                        <h3>Magazyn</h3>
                        <p>Sprzęt, wydania i stany — per magazyn, z historią każdego ruchu.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="cl-landing-stats">
            <div class="cl-landing-wrap">
                <div class="cl-landing-stats__grid">
                    <div>
                        <div class="cl-landing-stats__num font-mono">4</div>
                        <div class="cl-landing-stats__label">kraje operacyjne</div>
                    </div>
                    <div>
                        <div class="cl-landing-stats__num font-mono">6</div>
                        <div class="cl-landing-stats__label">modułów w jednym systemie</div>
                    </div>
                    <div>
                        <div class="cl-landing-stats__num font-mono">1</div>
                        <div class="cl-landing-stats__label">źródło prawdy o pracowniku</div>
                    </div>
                    <div>
                        <div class="cl-landing-stats__num font-mono">0</div>
                        <div class="cl-landing-stats__label">arkuszy potrzebnych do pracy</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cl-landing-section" id="jak-dziala">
            <div class="cl-landing-wrap">
                <div class="cl-landing-section__head">
                    <span class="cl-landing-kicker">Cykl rotacji</span>
                    <h2>Ścieżka jednego pracownika przez system.</h2>
                    <p>Od pierwszego kontaktu po kolejny wyjazd — każdy etap zapisany, nikt nie pyta „a kto to ustalał?”.</p>
                </div>
                <ol class="cl-landing-steps">
                    <li>
                        <span class="cl-landing-steps__tag font-mono">Etap 01</span>
                        <h3>Lead</h3>
                        <p>Kandydat pojawia się z Meta, polecenia lub bazy własnej — trafia do jednego lejka rekrutacyjnego.</p>
                    </li>
                    <li>
                        <span class="cl-landing-steps__tag font-mono">Etap 02</span>
                        <h3>Kontakt i proces</h3>
                        <p>Historia rozmów zapisana przy numerze telefonu — nikt nie dzwoni drugi raz do kogoś, kto już odmówił.</p>
                    </li>
                    <li>
                        <span class="cl-landing-steps__tag font-mono">Etap 03</span>
                        <h3>Zatrudnienie</h3>
                        <p>Kandydat zamienia się w pracownika bez przepisywania danych — jeden rekord.</p>
                    </li>
                    <li>
                        <span class="cl-landing-steps__tag font-mono">Etap 04</span>
                        <h3>Wyjazd</h3>
                        <p>Transfer, kwatera i przypisanie do projektu ustalane w systemie, widoczne dla logistyki.</p>
                    </li>
                    <li>
                        <span class="cl-landing-steps__tag font-mono">Etap 05</span>
                        <h3>Rotacja</h3>
                        <p>Powrót, kolejny wyjazd, zmiana projektu — cykl się powtarza, a historia zostaje.</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="cl-landing-cta">
            <div class="cl-landing-wrap">
                <span class="cl-landing-kicker">Gotowi zacząć</span>
                <h2>Twoja logistyka nie powinna mieszkać w skrzynce mailowej.</h2>
                <a href="{{ route('login') }}" class="btn btn-primary">Zaloguj się do systemu</a>
            </div>
        </section>

        <x-landing.footer />
    </div>
</x-guest-layout>
