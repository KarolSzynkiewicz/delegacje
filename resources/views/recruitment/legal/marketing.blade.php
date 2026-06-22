<x-layouts.legal-page title="Zgoda marketingowa">

    <p class="lead mb-4">
        Poniżej opisujemy, na czym polega dobrowolna zgoda marketingowa w procesie rekrutacji
        prowadzonym przez {{ config('app.name') }}.
    </p>

    <div class="info-box">
        <p class="mb-0">
            <strong>Ważne:</strong> zgoda marketingowa jest <strong>całkowicie dobrowolna</strong>.
            Możesz wysłać formularz rekrutacyjny bez jej zaznaczenia — nie wpłynie to negatywnie
            na rozpatrzenie Twojej kandydatury.
        </p>
    </div>

    <h2>1. Na co wyrażasz zgodę?</h2>
    <p>Zaznaczając to pole, wyrażasz zgodę na otrzymywanie od {{ config('app.name') }} informacji marketingowych dotyczących m.in.:</p>
    <ul>
        <li>nowych ofert pracy i możliwości zatrudnienia,</li>
        <li>programów rekrutacyjnych i wydarzeń branżowych,</li>
        <li>aktualności dotyczących firmy jako pracodawcy,</li>
        <li>komunikatów promujących kulturę organizacyjną i benefity pracownicze.</li>
    </ul>

    <h2>2. Kanały komunikacji</h2>
    <p>Informacje marketingowe mogą być przekazywane za pośrednictwem:</p>
    <ul>
        <li>wiadomości e-mail na adres podany w formularzu,</li>
        <li>wiadomości SMS lub telefonicznych — jeśli podałeś numer telefonu.</li>
    </ul>

    <h2>3. Jak to działa w praktyce?</h2>
    <ol>
        <li>Zaznaczasz opcjonalne pole „Wyrażam zgodę marketingową" w formularzu rekrutacyjnym.</li>
        <li>Twoja zgoda jest zapisywana wraz ze zgłoszeniem (data i czas złożenia).</li>
        <li>Możesz otrzymywać komunikaty marketingowe niezależnie od wyniku bieżącej rekrutacji.</li>
        <li>W każdej wiadomości marketingowej znajdziesz możliwość rezygnacji z dalszej komunikacji.</li>
    </ol>

    <h2>4. Okres obowiązywania zgody</h2>
    <p>
        Zgoda obowiązuje do momentu jej wycofania. Po wycofaniu zaprzestaniemy wysyłania
        komunikatów marketingowych, z zastrzeżeniem, że przetwarzanie do tego momentu było zgodne z prawem.
    </p>

    <h2>5. Wycofanie zgody</h2>
    <p>Możesz wycofać zgodę marketingową w dowolnym momencie poprzez:</p>
    <ul>
        <li>link „wypisz się" w otrzymanej wiadomości e-mail,</li>
        <li>wiadomość na adres:
            <a href="mailto:{{ config('mail.from.address', 'marketing@example.com') }}">{{ config('mail.from.address', 'marketing@example.com') }}</a>.
        </li>
    </ul>

    <h2>6. Brak zgody marketingowej</h2>
    <p>
        Jeśli nie zaznaczysz tego pola, nadal możesz wziąć udział w rekrutacji.
        Będziemy kontaktować się z Tobą wyłącznie w sprawach związanych z procesem rekrutacyjnym
        (np. zaproszenie na rozmowę, informacja o wyniku rekrutacji).
    </p>

</x-layouts.legal-page>
