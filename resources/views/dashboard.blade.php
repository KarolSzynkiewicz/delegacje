<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-dark mb-0">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            {{-- Hero Section --}}
            <x-ui.card class="mb-5">
                <div class="text-center py-5">
                    <h1 class="display-4 fw-bold mb-3">System Stocznia</h1>
                    <p class="lead text-muted mb-4">Kompleksowe zarządzanie logistyką i delegowaniem pracowników</p>
                    <p class="text-muted">System pomaga w codziennej pracy poprzez automatyzację przypisań, walidację dostępności pracowników oraz zarządzanie zasobami projektów.</p>
                </div>
            </x-ui.card>

            {{-- Główne Moduły --}}
            <div class="row g-4 mb-5">
                {{-- Dashboard Rentowności --}}
                @if(auth()->user()->hasPermission('profitability.view'))
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-graph-up-arrow text-success fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-success fw-semibold mb-3">Dashboard Rentowności</h3>
                                <p class="text-muted mb-3">
                                    Moduł analizy rentowności pozwala monitorować efektywność finansową projektów i pracowników. 
                                    Dzięki temu możesz szybko identyfikować najbardziej opłacalne projekty oraz optymalizować koszty.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Analizować rentowność poszczególnych projektów w czasie rzeczywistym</li>
                                        <li>Śledzić koszty i przychody związane z każdym projektem</li>
                                        <li>Oceniać efektywność finansową poszczególnych pracowników</li>
                                        <li>Generować raporty finansowe dla zarządu</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Moduł automatycznie agreguje dane z wszystkich projektów, przypisań i kosztów, 
                                        prezentując je w przejrzystych wykresach i tabelach. Dzięki temu możesz szybko 
                                        podejmować decyzje biznesowe bez konieczności ręcznego przeliczania danych.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
                @endif

                {{-- Widok Tygodniowy --}}
                @if(auth()->user()->hasPermission('weekly-overview.view'))
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-calendar-week text-info fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-info fw-semibold mb-3">Przegląd Tygodniowy</h3>
                                <p class="text-muted mb-3">
                                    Główny widok do zarządzania tygodniowymi przydziałami ekip. To centrum dowodzenia, 
                                    gdzie widzisz wszystkie projekty, pracowników, pojazdy i mieszkania w jednym miejscu.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Przeglądać wszystkie projekty i ich zapotrzebowanie na pracowników w wybranym tygodniu</li>
                                        <li>Sprawdzać, którzy pracownicy są przypisani do projektów i w jakich rolach</li>
                                        <li>Zarządzać przypisaniem pojazdów i mieszkań do pracowników</li>
                                        <li>Nawigować między tygodniami, aby planować przyszłe przydziały</li>
                                        <li>Edytować zapotrzebowanie na role bezpośrednio z widoku tygodniowego</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Zamiast przeglądać wiele różnych widoków, wszystko masz w jednym miejscu. 
                                        System automatycznie pokazuje, którzy pracownicy nie mają jeszcze przypisanego 
                                        pojazdu lub mieszkania, co ułatwia szybkie uzupełnianie brakujących informacji. 
                                        Widok jest interaktywny - możesz dodawać pracowników, edytować zapotrzebowanie 
                                        i przypisywać zasoby bezpośrednio z tego widoku.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
                @endif

                {{-- Projekty --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-folder text-primary fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-primary fw-semibold mb-3">Projekty</h3>
                                <p class="text-muted mb-3">
                                    Moduł zarządzania projektami to miejsce, gdzie tworzysz i zarządzasz wszystkimi 
                                    projektami w systemie. Każdy projekt może mieć swoje zapotrzebowanie na role, 
                                    lokalizację i inne szczegóły.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Tworzyć nowe projekty z nazwą, opisem i lokalizacją</li>
                                        <li>Definiować zapotrzebowanie na role (np. 2 spawaczy, 1 dekarza) w określonych okresach</li>
                                        <li>Przeglądać wszystkie przypisania pracowników do projektu</li>
                                        <li>Edytować i zarządzać szczegółami projektu</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        System automatycznie sprawdza, czy zapotrzebowanie na role jest spełnione 
                                        przez przypisanych pracowników. Dzięki temu zawsze wiesz, czy projekt ma 
                                        wystarczającą liczbę osób w odpowiednich rolach. Moduł integruje się z 
                                        widokiem tygodniowym, gdzie możesz szybko zobaczyć status wszystkich projektów.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Przypisania --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-person-check text-success fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-success fw-semibold mb-3">Przypisania Pracowników do Projektów</h3>
                                <p class="text-muted mb-3">
                                    Moduł przypisań to serce systemu - tutaj przypisujesz pracowników do projektów 
                                    z pełną walidacją dostępności i wymagań.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Przypisywać pracowników do projektów w określonych okresach</li>
                                        <li>Określać rolę pracownika w projekcie (spawacz, dekarz, itp.)</li>
                                        <li>Zarządzać statusem przypisań (aktywne, zakończone, anulowane)</li>
                                        <li>Przeglądać historię wszystkich przypisań</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        System automatycznie waliduje każde przypisanie przed zapisaniem. Sprawdza, 
                                        czy pracownik ma rotację pokrywającą cały okres, czy ma wszystkie wymagane 
                                        dokumenty ważne w tym czasie, czy nie jest już przypisany do innego projektu 
                                        oraz czy projekt ma zapotrzebowanie na daną rolę. Jeśli coś nie pasuje, 
                                        otrzymujesz dokładny komunikat z powodem. To eliminuje błędy i konflikty 
                                        w przypisaniach.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Pracownicy --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-people text-purple fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-purple fw-semibold mb-3">Pracownicy</h3>
                                <p class="text-muted mb-3">
                                    Baza danych wszystkich pracowników delegowanych. Tutaj zarządzasz danymi osobowymi, 
                                    rolami, dokumentami i historią każdego pracownika.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Dodawać i edytować dane pracowników (imię, nazwisko, kontakt, role)</li>
                                        <li>Zarządzać dokumentami pracowników (uprawnienia, certyfikaty, ważność)</li>
                                        <li>Przeglądać rotacje dostępności każdego pracownika</li>
                                        <li>Sprawdzać historię przypisań do projektów</li>
                                        <li>Zarządzać przypisaniami pojazdów i mieszkań</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Wszystkie informacje o pracowniku są w jednym miejscu. System automatycznie 
                                        sprawdza ważność dokumentów przed przypisaniem do projektu, więc nie musisz 
                                        ręcznie weryfikować, czy pracownik może pracować w danym projekcie. Widzisz 
                                        również pełną historię pracy pracownika, co ułatwia planowanie przyszłych przydziałów.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Rotacje Pracowników --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-arrow-repeat text-info fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-info fw-semibold mb-3">Rotacje Pracowników</h3>
                                <p class="text-muted mb-3">
                                    Rotacje określają okresy, w których pracownik jest dostępny do pracy. 
                                    To kluczowy element systemu, który decyduje o możliwości przypisania pracownika do projektu.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Definiować okresy dostępności pracowników (data rozpoczęcia i zakończenia)</li>
                                        <li>Przeglądać wszystkie rotacje z możliwością filtrowania po pracowniku, statusie i datach</li>
                                        <li>Anulować rotacje, jeśli pracownik nie będzie dostępny</li>
                                        <li>Dodawać uwagi do rotacji</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Status rotacji jest automatycznie obliczany przez system (Zaplanowana, Aktywna, 
                                        Zakończona) na podstawie dat, więc nie musisz ręcznie aktualizować statusów. 
                                        System używa rotacji do walidacji przypisań - pracownik może być przypisany tylko 
                                        w okresie, gdy ma aktywną rotację. Dzięki filtrom możesz szybko znaleźć dostępnych 
                                        pracowników w określonym okresie.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Pojazdy --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-car-front text-warning fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-warning fw-semibold mb-3">Pojazdy</h3>
                                <p class="text-muted mb-3">
                                    Zarządzanie flotą pojazdów firmowych. Moduł pozwala śledzić wszystkie pojazdy, 
                                    ich stan techniczny, przeglądy i przypisania do pracowników.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Dodawać i edytować dane pojazdów (numer rejestracyjny, marka, model)</li>
                                        <li>Śledzić stan techniczny pojazdów</li>
                                        <li>Zarządzać terminami przeglądów</li>
                                        <li>Przeglądać historię przypisań pojazdów do pracowników</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        W widoku tygodniowym widzisz, którzy pracownicy nie mają jeszcze przypisanego 
                                        pojazdu, co ułatwia szybkie uzupełnianie brakujących informacji. System pomaga 
                                        unikać konfliktów - możesz sprawdzić, czy pojazd nie jest już przypisany do 
                                        innego pracownika w tym samym czasie.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Mieszkania --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-house text-danger fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-danger fw-semibold mb-3">Mieszkania</h3>
                                <p class="text-muted mb-3">
                                    Zarządzanie akomodacjami dla pracowników delegowanych. Moduł pozwala śledzić 
                                    dostępne mieszkania, ich pojemność i przypisania.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Dodawać i edytować dane mieszkań (nazwa, adres, pojemność)</li>
                                        <li>Śledzić, ilu pracowników może pomieścić każde mieszkanie</li>
                                        <li>Przeglądać historię przypisań mieszkań do pracowników</li>
                                        <li>Zarządzać dostępnością mieszkań</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Podobnie jak z pojazdami, w widoku tygodniowym widzisz, którzy pracownicy 
                                        nie mają jeszcze przypisanego mieszkania. System sprawdza pojemność mieszkań, 
                                        więc możesz uniknąć nadmiernego obciążenia. Wszystkie przypisania są widoczne 
                                        w jednym miejscu, co ułatwia planowanie logistyczne.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Przypisania Pojazdów --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-car-front-fill text-primary fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-primary fw-semibold mb-3">Przypisania Pojazdów</h3>
                                <p class="text-muted mb-3">
                                    Moduł do zarządzania przypisaniami pojazdów do pracowników w określonych okresach.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Przypisywać pojazdy do pracowników na określone okresy</li>
                                        <li>Przeglądać wszystkie aktywne i historyczne przypisania</li>
                                        <li>Edytować i anulować przypisania</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Możesz przypisywać pojazdy bezpośrednio z widoku tygodniowego lub z profilu 
                                        pracownika. System pomaga unikać konfliktów - widzisz, czy pojazd nie jest 
                                        już przypisany w tym samym czasie do innego pracownika.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Przypisania Mieszkań --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-house-fill text-danger fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-danger fw-semibold mb-3">Przypisania Mieszkań</h3>
                                <p class="text-muted mb-3">
                                    Moduł do zarządzania przypisaniami mieszkań do pracowników w określonych okresach.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Przypisywać mieszkania do pracowników na określone okresy</li>
                                        <li>Przeglądać wszystkie aktywne i historyczne przypisania</li>
                                        <li>Edytować i anulować przypisania</li>
                                        <li>Sprawdzać wykorzystanie pojemności mieszkań</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Podobnie jak z pojazdami, możesz przypisywać mieszkania z widoku tygodniowego 
                                        lub z profilu pracownika. System sprawdza pojemność mieszkań, więc unikasz 
                                        nadmiernego obciążenia. Wszystkie informacje są w jednym miejscu, co ułatwia 
                                        planowanie logistyczne.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Lokalizacje --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-geo-alt text-success fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-success fw-semibold mb-3">Lokalizacje</h3>
                                <p class="text-muted mb-3">
                                    Zarządzanie miejscami pracy (stoczniami) i ich danymi kontaktowymi.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Dodawać i edytować lokalizacje projektów (nazwa, adres, dane kontaktowe)</li>
                                        <li>Przypisywać lokalizacje do projektów</li>
                                        <li>Przeglądać wszystkie lokalizacje w systemie</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Centralne miejsce do zarządzania wszystkimi lokalizacjami projektów. 
                                        Dzięki temu masz szybki dostęp do danych kontaktowych i adresów wszystkich 
                                        miejsc pracy, co ułatwia komunikację i planowanie logistyczne.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Dokumenty Pracowników --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-file-earmark-medical text-warning fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-warning fw-semibold mb-3">Dokumenty Pracowników</h3>
                                <p class="text-muted mb-3">
                                    Zarządzanie dokumentami pracowników (uprawnienia, certyfikaty) z automatyczną 
                                    walidacją ważności przed przypisaniem do projektu.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Dodawać dokumenty pracowników (okresowe lub bezokresowe)</li>
                                        <li>Definiować daty ważności dokumentów</li>
                                        <li>Przeglądać wszystkie dokumenty pracownika w jednym miejscu</li>
                                        <li>Sprawdzać ważność dokumentów przed przypisaniem</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        System automatycznie sprawdza ważność dokumentów przed przypisaniem pracownika 
                                        do projektu. Jeśli dokument nie jest ważny w okresie przypisania, system zablokuje 
                                        przypisanie i poinformuje Cię o przyczynie. To eliminuje ryzyko przypisania 
                                        pracownika bez odpowiednich uprawnień. Możesz również przeglądać wszystkie 
                                        dokumenty pracownika z profilu, co ułatwia zarządzanie.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                {{-- Ewidencja Godzin --}}
                @if(auth()->user()->hasPermission('time-logs.viewAny'))
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-clock text-info fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-info fw-semibold mb-3">Ewidencja Godzin</h3>
                                <p class="text-muted mb-3">
                                    Rejestracja rzeczywistych godzin pracy pracowników w projektach.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Rejestrować rzeczywiste godziny pracy pracowników</li>
                                        <li>Śledzić różnice między planowanymi a rzeczywistymi godzinami</li>
                                        <li>Generować raporty godzinowe dla rozliczeń</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Dzięki ewidencji godzin możesz dokładnie śledzić rzeczywisty czas pracy 
                                        pracowników, co jest kluczowe dla rozliczeń i analizy efektywności projektów.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
                @endif

                {{-- Stawki Pracowników --}}
                <div class="col-12">
                    <x-ui.card>
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-currency-exchange text-success fs-1 me-4"></i>
                            <div class="flex-grow-1">
                                <h3 class="text-success fw-semibold mb-3">Stawki Pracowników</h3>
                                <p class="text-muted mb-3">
                                    Zarządzanie stawkami godzinowymi pracowników, które są używane do obliczania 
                                    kosztów projektów i rozliczeń.
                                </p>
                                <div class="mb-3">
                                    <h5 class="fw-semibold mb-2">Co możesz tutaj zrobić:</h5>
                                    <ul class="text-muted mb-0">
                                        <li>Definiować stawki godzinowe dla każdego pracownika</li>
                                        <li>Ustawiać różne stawki dla różnych okresów</li>
                                        <li>Przeglądać historię zmian stawek</li>
                                    </ul>
                                </div>
                                <div>
                                    <h5 class="fw-semibold mb-2">Jak to pomaga w codziennej pracy:</h5>
                                    <p class="text-muted mb-0">
                                        Stawki są automatycznie używane przez system do obliczania kosztów projektów 
                                        w module rentowności. Dzięki temu masz zawsze aktualne dane finansowe bez 
                                        konieczności ręcznego przeliczania.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            {{-- Podsumowanie --}}
            <x-ui.card class="mt-5">
                <div class="text-center py-4">
                    <h3 class="fw-semibold mb-3">Jak zacząć pracę z systemem?</h3>
                    <p class="text-muted mb-0">
                        System został zaprojektowany tak, aby ułatwić codzienną pracę. Zacznij od <strong>Widoku Tygodniowego</strong>, 
                        gdzie masz pełny przegląd wszystkich projektów i przydziałów. Następnie możesz dodawać projekty, 
                        definiować zapotrzebowanie na role, tworzyć rotacje dla pracowników i przypisywać ich do projektów. 
                        System automatycznie waliduje wszystkie operacje, więc możesz być pewien, że wszystko jest zgodne 
                        z wymaganiami.
                    </p>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
