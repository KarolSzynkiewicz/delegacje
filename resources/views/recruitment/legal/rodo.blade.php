<x-layouts.legal-page title="Informacja o przetwarzaniu danych osobowych (RODO)">

    <p class="lead mb-4">
        Niniejszy dokument wyjaśnia, w jaki sposób {{ config('app.name') }} przetwarza dane osobowe
        kandydatów zgłaszających się przez formularz rekrutacyjny, zgodnie z Rozporządzeniem Parlamentu
        Europejskiego i Rady (UE) 2016/679 (RODO).
    </p>

    <div class="info-box">
        <p class="mb-0">
            <strong>Administrator danych:</strong> {{ config('app.name') }}<br>
            <strong>Kontakt w sprawach ochrony danych:</strong>
            <a href="mailto:{{ config('mail.from.address', 'rodo@example.com') }}">{{ config('mail.from.address', 'rodo@example.com') }}</a>
        </p>
    </div>

    <h2>1. Jakie dane zbieramy?</h2>
    <p>W formularzu rekrutacyjnym możesz podać m.in.:</p>
    <ul>
        <li>imię i nazwisko,</li>
        <li>adres e-mail,</li>
        <li>numer telefonu,</li>
        <li>informacje o stanowisku, o które się ubiegasz,</li>
        <li>list motywacyjny / wiadomość,</li>
        <li>zdjęcie (opcjonalnie).</li>
    </ul>

    <h2>2. W jakim celu przetwarzamy dane?</h2>
    <p>Dane osobowe przetwarzamy w celu:</p>
    <ul>
        <li>przeprowadzenia procesu rekrutacji na wybrane stanowisko,</li>
        <li>kontaktu z kandydatem w sprawie zgłoszenia,</li>
        <li>oceny kwalifikacji i doświadczenia zawodowego,</li>
        <li>spełnienia obowiązków prawnych ciążących na administratorze.</li>
    </ul>

    <h2>3. Podstawa prawna przetwarzania</h2>
    <ul>
        <li><strong>Art. 6 ust. 1 lit. a RODO</strong> — Twoja dobrowolna zgoda (np. na przetwarzanie w przyszłych rekrutacjach lub marketing).</li>
        <li><strong>Art. 6 ust. 1 lit. b RODO</strong> — działania przed zawarciem umowy (proces rekrutacji).</li>
        <li><strong>Art. 6 ust. 1 lit. c RODO</strong> — wypełnienie obowiązków prawnych.</li>
        <li><strong>Art. 6 ust. 1 lit. f RODO</strong> — prawnie uzasadniony interes administratora (np. obrona przed roszczeniami).</li>
    </ul>

    <h2>4. Okres przechowywania danych</h2>
    <p>
        Dane z bieżącej rekrutacji przechowujemy przez czas trwania procesu rekrutacji oraz przez okres
        niezbędny do obrony przed ewentualnymi roszczeniami — nie dłużej niż jest to konieczne.
        Jeśli wyrazisz osobną zgodę na przetwarzanie danych w przyszłych rekrutacjach, dane mogą być
        przechowywane dłużej — do momentu wycofania zgody.
    </p>

    <h2>5. Twoje prawa</h2>
    <p>Przysługują Ci następujące prawa:</p>
    <ul>
        <li>prawo dostępu do swoich danych,</li>
        <li>prawo do sprostowania danych,</li>
        <li>prawo do usunięcia danych („prawo do bycia zapomnianym"),</li>
        <li>prawo do ograniczenia przetwarzania,</li>
        <li>prawo do przenoszenia danych,</li>
        <li>prawo do wycofania zgody w dowolnym momencie (bez wpływu na zgodność z prawem przetwarzania przed wycofaniem),</li>
        <li>prawo wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych (PUODO).</li>
    </ul>

    <h2>6. Odbiorcy danych</h2>
    <p>
        Dane mogą być udostępniane podmiotom świadczącym usługi na rzecz administratora
        (np. hosting, systemy IT, narzędzia komunikacji), wyłącznie w zakresie niezbędnym
        do realizacji celów przetwarzania i na podstawie odpowiednich umów powierzenia przetwarzania danych.
    </p>

    <h2>7. Dobrowolność podania danych</h2>
    <p>
        Podanie danych oznaczonych jako wymagane jest konieczne do wzięcia udziału w rekrutacji.
        Brak podania tych danych uniemożliwi rozpatrzenie Twojego zgłoszenia.
    </p>

</x-layouts.legal-page>
