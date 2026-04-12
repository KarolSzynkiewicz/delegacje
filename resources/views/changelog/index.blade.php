<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dziennik zmian systemowych" />
    </x-slot>

    <div class="container-xxl">
        <p class="text-muted small mb-4">
            Krótki opis zmian wdrożonych w aplikacji (aktualizowane ręcznie). Format jak wpisy w commicie — bez szczegółów technicznych dla użytkownika końcowego.
        </p>

        <x-ui.card label="2026-04-11 — weekend (od piątku 10.04)">
            <ul class="small mb-0 ps-3" style="line-height: 1.75;">
                <li><strong>Zadania:</strong> szybka edycja na liście; załączniki; komentarze w wątkach i polubienia; porządki w nagłówkach i zakładkach projektu.</li>
                <li><strong>Płace / rozliczenia:</strong> stronicowanie listy (100 wierszy), poprawki nagłówków i widoku listy.</li>
                <li><strong>Ewidencja godzin:</strong> siatka miesięczna bardziej przyjazna na telefonie (przewijanie poziome).</li>
                <li><strong>Logistyka / transfer:</strong> poprawa zapisu przy ostrzeżeniu OC lub przeglądu (potwierdzenie bez cichego blokowania); ten sam schemat przy potwierdzaniu przypisania auta w wyjeździe.</li>
                <li><strong>Przegląd tygodniowy:</strong> kafelek <strong>Projekty</strong> (koniec projektu w bieżącym miesiącu) zamiast sumy „Łącznie”; lista w podglądzie.</li>
                <li><strong>Audyt / logi systemowe:</strong> migracja tabeli; podgląd zmian w panelu (przed / po, czytelne statusy); bez surowego JSON w tabeli; poprawne „przed” przy edycji rekordów (np. zmiana statusu).</li>
                <li><strong>Administracja:</strong> statyczna strona <strong>Dziennik zmian</strong> (ten newsletter) pod tym samym dostępem co logi.</li>
            </ul>
        </x-ui.card>
    </div>
</x-app-layout>
