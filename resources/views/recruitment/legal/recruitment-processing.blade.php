<x-layouts.legal-page title="Zgoda na przetwarzanie danych w celu rekrutacji">

    <p class="lead mb-4">
        Poniżej wyjaśniamy, na czym polega zgoda na przetwarzanie danych osobowych
        w ramach bieżącej oraz przyszłych rekrutacji prowadzonych przez {{ config('app.name') }}.
    </p>

    <div class="info-box">
        <p class="mb-0">
            <strong>Ważne:</strong> ta zgoda jest <strong>wymagana</strong>, abyśmy mogli rozpatrzyć Twoje zgłoszenie
            i — jeśli wyrazisz na to zgodę w treści formularza — przechowywać Twoje dane na potrzeby
            przyszłych procesów rekrutacyjnych.
        </p>
    </div>

    <h2>1. Zakres zgody</h2>
    <p>Zaznaczając to pole w formularzu, wyrażasz zgodę na przetwarzanie przez {{ config('app.name') }} Twoich danych osobowych, w tym:</p>
    <ul>
        <li>danych identyfikacyjnych (imię, nazwisko),</li>
        <li>danych kontaktowych (e-mail, telefon),</li>
        <li>informacji o preferowanym stanowisku,</li>
        <li>listu motywacyjnego i innych informacji dobrowolnie podanych w formularzu,</li>
        <li>zdjęcia (jeśli je załączysz),</li>
    </ul>
    <p>w celu:</p>
    <ul>
        <li><strong>bieżącej rekrutacji</strong> — na stanowisko, o które się ubiegasz,</li>
        <li><strong>przyszłych rekrutacji</strong> — gdy pojawią się oferty pracy odpowiadające Twoim kwalifikacjom lub preferencjom.</li>
    </ul>

    <h2>2. Jak to działa w praktyce?</h2>
    <ol>
        <li>Składasz zgłoszenie przez formularz na stronie <a href="{{ route('recruitment.apply') }}">/rekrutacja</a>.</li>
        <li>Twoje dane trafiają do bazy kandydatur i są widoczne dla upoważnionych pracowników HR / rekruterów.</li>
        <li>Jeśli nie zostaniesz zatrudniony w bieżącym procesie, Twoje dane mogą zostać zachowane na potrzeby przyszłych rekrutacji — o ile wyraziłeś na to zgodę.</li>
        <li>Możemy skontaktować się z Tobą, gdy pojawi się stanowisko pasujące do Twojego profilu.</li>
    </ol>

    <h2>3. Okres przechowywania</h2>
    <p>
        W ramach bieżącej rekrutacji dane przechowujemy przez czas trwania procesu oraz okres
        przedawnienia ewentualnych roszczeń. W ramach przyszłych rekrutacji — do momentu wycofania zgody
        lub wniesienia sprzeciwu, nie dłużej niż jest to uzasadnione (np. 24 miesiące od ostatniego kontaktu,
        chyba że przepisy wymagają innego okresu).
    </p>

    <h2>4. Wycofanie zgody</h2>
    <p>
        Zgodę możesz wycofać w dowolnym momencie, wysyłając wiadomość na adres:
        <a href="mailto:{{ config('mail.from.address', 'rekrutacja@example.com') }}">{{ config('mail.from.address', 'rekrutacja@example.com') }}</a>.
        Wycofanie zgody nie wpływa na zgodność z prawem przetwarzania dokonanego przed jej wycofaniem.
        Po wycofaniu zgody na przyszłe rekrutacje Twoje dane zostaną usunięte lub zanonimizowane,
        o ile nie istnieje inna podstawa prawna ich przechowywania.
    </p>

    <h2>5. Dobrowolność</h2>
    <p>
        Zgoda jest dobrowolna, lecz <strong>wymagana do wysłania formularza</strong>.
        Bez niej nie możemy przyjąć i rozpatrzyć Twojego zgłoszenia rekrutacyjnego.
    </p>

</x-layouts.legal-page>
