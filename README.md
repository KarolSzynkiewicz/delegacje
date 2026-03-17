# Stocznia - Kompleksowy System ERP dla Firm Delegujących Pracowników

**Stocznia** to zaawansowana aplikacja webowa stworzona w oparciu o framework **Laravel**, która rewolucjonizuje zarządzanie logistyką, zasobami ludzkimi i finansami w firmach delegujących pracowników. System łączy w sobie pełną funkcjonalność ERP z intuicyjnym interfejsem użytkownika, oferując kompleksowe rozwiązanie dla zarządzania projektami, pracownikami, pojazdami, akomodacjami, rozliczeniami i analityką finansową.

**Dlaczego Stocznia?**
- 🚀 **Kompleksowe rozwiązanie** - wszystko w jednym miejscu: od planowania projektów po generowanie list płac
- 💰 **Pełna kontrola finansowa** - dashboard rentowności, koszty stałe i zmienne, analiza marży
- 👥 **Zaawansowane zarządzanie personelem** - rotacje, dokumenty, oceny, stawki godzinowe
- 🚗 **Inteligentna logistyka** - wyjazdy, powroty, przypisania pojazdów i mieszkań z automatycznym czyszczeniem konfliktów
- 📊 **Widok miesięczny godzin** - zaawansowany widok z grupowaniem po projektach i bulk update
- 🔐 **Pełny RBAC** - dynamiczna tabela uprawnień dostosowana do potrzeb biznesowych
- ⚡ **Wydajność** - cache'owanie, optymalizacja zapytań, szybkie działanie nawet przy dużych ilościach danych

## 🔐 System Autoryzacji - Pełny RBAC

Aplikacja wykorzystuje **pełny system RBAC (Role-Based Access Control)** z dynamiczną tabelą uprawnień, który możesz konfigurować zgodnie z potrzebami biznesowymi.

**Kluczowe cechy:**
- ✅ **Dynamiczna tabela uprawnień** - zarządzanie dostępem do zasobów przez intuicyjną tabelkę w UI
- ✅ **Pełny RBAC** - role, uprawnienia, hierarchia dostępu
- ✅ **Automatyczne generowanie uprawnień** - system automatycznie tworzy uprawnienia z route
- ✅ **Elastyczna konfiguracja** - dostosuj uprawnienia do struktury organizacji
- ✅ **Wydajność** - cache uprawnień (24h) i mapowań route (1h)
- ✅ **Bezpieczeństwo** - middleware automatycznie sprawdza uprawnienia dla każdego requestu
- ✅ **Zero konfiguracji** - nowe route automatycznie pojawiają się w tabelce uprawnień

**Jak to działa:**
1. System automatycznie generuje listę uprawnień z route aplikacji
2. W tabelce ról zaznaczasz, które uprawnienia ma dana rola
3. Przypisujesz role do użytkowników
4. System automatycznie sprawdza uprawnienia przy każdym requestcie

**Szczegółowa dokumentacja:** Zobacz [authorization.readme.md](authorization.readme.md) dla pełnego opisu działania systemu autoryzacji.

---

## 🚀 Funkcjonalności

Aplikacja oferuje następujące moduły:

| Moduł | Opis | Kluczowe Dane |
| :--- | :--- | :--- |
| **Autentykacja** | Logowanie, rejestracja, resetowanie hasła (Laravel Breeze). | Użytkownicy, hasła. |
| **Pracownicy** | Zarządzanie personelem delegowanym. | Imię, Nazwisko, Kontakt, Rola (Spawacz/Dekarz), Dokumenty. |
| **Rotacje** | Definiowanie okresów dostępności pracowników. | Data rozpoczęcia, Data zakończenia, Status (automatyczny). |
| **Akomodacje** | Zarządzanie dostępnymi mieszkaniami. | Nazwa, Adres, Pojemność (liczba osób). |
| **Pojazdy** | Zarządzanie flotą pojazdów. | Numer Rejestracyjny, Marka, Model, Stan Techniczny, Przegląd. |
| **Lokalizacje** | Zarządzanie miejscami pracy (stoczniami). | Nazwa, Adres, Dane kontaktowe. |
| **Projekty** | Tworzenie i zarządzanie projektami. | Nazwa, Opis, Zapotrzebowanie na role. |
| **Przypisania** | Przypisywanie pracowników do projektów z walidacją dostępności. | Pracownik, Projekt, Rola, Daty, Status. |
| **Widok Tygodniowy** | Tygodniowy przegląd wszystkich projektów, pracowników i zasobów. | Projekty, Pracownicy, Pojazdy, Mieszkania, Zapotrzebowanie. |
| **Ewidencja Godzin - Widok Miesięczny** | Zaawansowany widok miesięczny do wprowadzania godzin pracy z grupowaniem po projektach. | Projekt, Pracownik, Dzień, Godziny, Bulk update. |
| **Rozliczenia (Payroll)** | Generowanie list płac dla pracowników na podstawie godzin i stawek. | Okres, Godziny, Stawka godzinowa, Waluta, Status. |
| **Stawki Pracowników** | Zarządzanie stawkami godzinowymi pracowników dla różnych projektów. | Pracownik, Projekt, Stawka, Waluta, Okres. |
| **Koszty Stałe** | Zarządzanie kosztami stałymi firmy (szablony i wpisy). | Nazwa, Kwota, Okres, Waluta, Kategoria. |
| **Koszty Zmienne** | Zarządzanie kosztami zmiennymi projektów. | Projekt, Kwota, Data, Waluta, Opis. |
| **Koszty Transportu** | Zarządzanie kosztami transportu między lokalizacjami. | Trasa, Koszt, Waluta, Data. |
| **Dashboard Finansowy** | Analiza rentowności firmy i projektów. | Przychody, Koszty, Marża, Rentowność. |
| **Magazyn** | Zarządzanie sprzętem i wydaniami dla pracowników. | Sprzęt, Ilość, Pracownik, Data wydania/zwrotu. |
| **Wyjazdy i Powroty** | Zarządzanie wyjazdami pracowników do projektów i powrotami. | Data, Pracownicy, Pojazd, Lokalizacja, Status. |
| **Zadania Projektowe** | Zarządzanie zadaniami w projektach z przypisaniem do pracowników. | Zadanie, Projekt, Pracownik, Status, Termin. |
| **Oceny Pracowników** | System oceniania pracowników przez kierowników projektów. | Pracownik, Projekt, Ocena, Uwagi, Data. |
| **Użytkownicy Systemu** | Zarządzanie użytkownikami systemu i ich rolami. | Użytkownik, Email, Role, Uprawnienia. |
| **Role i Uprawnienia** | Pełny RBAC - zarządzanie rolami i uprawnieniami użytkowników. | Rola, Uprawnienia, Dynamiczna tabela. |
| **Akcje Systemowe** | Narzędzia administracyjne (czyszczenie cache, odświeżanie uprawnień). | Cache, Uprawnienia, Optymalizacja. |
| **Raporty** | Generowanie raportów z delegacji (w rozwoju). | Typy raportów, eksport PDF/Excel. |

---


### Dashboard

Po zalogowaniu zobaczysz **Dashboard** z dostępem do wszystkich modułów systemu:

- **Przegląd Tygodniowy** - główny widok do zarządzania tygodniowymi przydziałami
- **Projekty** - zarządzanie projektami i zapotrzebowaniem
- **Pracownicy** - baza pracowników
- **Rotacje Pracowników** - zarządzanie dostępnością pracowników
- **Pojazdy** - flota pojazdów
- **Mieszkania** - akomodacje
- I inne...

---

## 📋 Podstawowy Workflow - Jak Przypisać Pracownika do Projektu

### Krok 1: Utwórz Projekt

1. Z Dashboard kliknij **"Projekty"**
2. Kliknij **"Dodaj Projekt"** (przycisk w prawym górnym rogu)
3. Wypełnij formularz:
   - **Nazwa projektu** (np. "Remont Stoczni Gdańskiej")
   - **Opis** (opcjonalnie)
4. Kliknij **"Zapisz"**

### Krok 2: Zdefiniuj Zapotrzebowanie na Role

1. W widoku projektu kliknij **"Zapotrzebowanie"** lub **"Dodaj Zapotrzebowanie"**
2. Wypełnij formularz:
   - **Data od** i **Data do** (okres zapotrzebowania)
   - Dla każdej roli podaj **Ilość potrzebnych osób** (np. 2 spawaczy, 1 dekarza)
   - **Uwagi** (opcjonalnie)
3. Kliknij **"Zapisz"**

### Krok 3: Dodaj Rotację dla Pracownika

**Rotacja określa okres, w którym pracownik jest dostępny do pracy.**

1. Z Dashboard kliknij **"Rotacje Pracowników"**
2. Kliknij **"Dodaj Rotację"**
3. Wybierz **Pracownika** z listy
4. Wprowadź:
   - **Data rozpoczęcia** (od kiedy pracownik jest dostępny)
   - **Data zakończenia** (do kiedy pracownik jest dostępny)
   - **Uwagi** (opcjonalnie)
5. Kliknij **"Zapisz"**
   - Status rotacji jest automatyczny: **Zaplanowana** (przyszłość), **Aktywna** (obecnie), **Zakończona** (przeszłość)
   - Możesz ręcznie ustawić status **Anulowana**

**Alternatywnie:** Możesz dodać rotację bezpośrednio z profilu pracownika:
1. Kliknij **"Pracownicy"** → wybierz pracownika
2. Przejdź do zakładki **"Rotacje"**
3. Kliknij **"Dodaj Rotację"**

### Krok 4: Dodaj Dokumenty Pracownika

**System sprawdza ważność dokumentów przed przypisaniem do projektu.**

1. Z Dashboard kliknij **"Pracownicy"**
2. Wybierz pracownika
3. Przejdź do zakładki **"Dokumenty"**
4. Kliknij **"Dodaj Dokument"**
5. Wybierz **Typ dokumentu** (np. "Uprawnienia spawacza")
6. Wypełnij:
   - **Rodzaj:** Okresowy lub Bezokresowy
   - **Data ważności od** (i **Data ważności do** dla okresowych)
7. Kliknij **"Zapisz"**

### Krok 5: Przypisz Pracownika do Projektu

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. Kliknij **"Przypisania"** lub **"Dodaj Przypisanie"**
3. Wypełnij formularz:
   - **Pracownik** - wybierz z listy (niedostępni są wyszarzeni z powodem)
   - **Rola w Projekcie** - musi być zgodna z rolami pracownika
   - **Data rozpoczęcia** i **Data zakończenia**
   - **Status** (domyślnie: Aktywne)
4. Kliknij **"Zapisz"**

**System automatycznie sprawdza:**
- ✅ Czy pracownik ma rotację pokrywającą cały okres przypisania
- ✅ Czy pracownik ma wszystkie wymagane dokumenty ważne w tym okresie
- ✅ Czy pracownik nie jest już przypisany do innego projektu w tym czasie
- ✅ Czy projekt ma zapotrzebowanie na tę rolę w danym okresie

Jeśli któryś warunek nie jest spełniony, zobaczysz komunikat błędu z dokładnym powodem.

### Krok 6: Przypisz Pojazd i Mieszkanie (Opcjonalnie)

**Z widoku tygodniowego:**

1. Z Dashboard kliknij **"Przegląd Tygodniowy"**
2. Wybierz tydzień (użyj przycisków "Poprzedni Tydzień" / "Następny Tydzień")
3. W karcie projektu znajdź sekcję **"Auta w projekcie"** lub **"Domy w projekcie"**
4. Dla pracowników bez auta/mieszkania kliknij przycisk **"Auto"** lub **"Dom"**
5. Wybierz pojazd/mieszkanie i daty
6. Kliknij **"Zapisz"**

**Alternatywnie z profilu pracownika:**

1. Kliknij **"Pracownicy"** → wybierz pracownika
2. Przejdź do zakładki **"Pojazdy"** lub **"Mieszkania"**
3. Kliknij **"Dodaj Przypisanie"**

---

## 📅 Przegląd Tygodniowy - Główny Widok Zarządzania

**Przegląd Tygodniowy** to najważniejszy widok do zarządzania przydziałami:

### Jak używać:

1. Z Dashboard kliknij **"Przegląd Tygodniowy"**
2. Użyj przycisków **"Poprzedni Tydzień"** / **"Następny Tydzień"** do nawigacji
3. Dla każdego projektu zobaczysz:
   - **Zapotrzebowanie** - tabela z rolami, ilością potrzebnych i przypisanych osób
   - **Osoby w projekcie** - lista przypisanych pracowników z rolami
   - **Auta w projekcie** - przypisane pojazdy i pracownicy bez auta
   - **Domy w projekcie** - przypisane mieszkania i pracownicy bez domu

### Szybkie akcje:

- **Edytuj zapotrzebowanie** - kliknij przycisk "Edytuj" w sekcji zapotrzebowania
- **Dodaj pracownika** - kliknij "Dodaj" w sekcji osób
- **Przypisz auto/dom** - kliknij przycisk "Auto" lub "Dom" przy pracowniku bez przypisania

---

## 💰 Rozliczenia i Listy Płac (Payroll)

System automatycznie generuje listy płac dla pracowników na podstawie zarejestrowanych godzin pracy i stawek godzinowych.

### Generowanie Listy Płac dla Pojedynczego Pracownika

1. Z Dashboard kliknij **"Rozliczenia"** (Payroll)
2. Kliknij **"Dodaj Listę Płac"**
3. Wybierz **Pracownika** z listy
4. Wprowadź:
   - **Okres od** i **Okres do** (daty rozliczenia)
   - **Uwagi** (opcjonalnie)
5. Kliknij **"Generuj"**

System automatycznie:
- ✅ Znajduje wszystkie TimeLogs (zarejestrowane godziny) dla pracownika w danym okresie
- ✅ Oblicza kwotę na podstawie stawek godzinowych (EmployeeRate) dla każdego projektu
- ✅ Uwzględnia różne waluty (PLN, EUR, USD)
- ✅ Tworzy niezmienny snapshot listy płac (nie można jej przeliczyć po utworzeniu)
- ✅ Uwzględnia zaliczki (Advances) i korekty (Adjustments)

### Generowanie List Płac dla Wszystkich Pracowników Jednym Guzikiem

1. Z Dashboard kliknij **"Rozliczenia"** (Payroll)
2. Kliknij **"Generuj dla Wszystkich"**
3. Wprowadź:
   - **Okres od** i **Okres do**
   - **Uwagi** (opcjonalnie)
4. Kliknij **"Generuj"**

System automatycznie:
- ✅ Znajduje wszystkich pracowników, którzy mają zarejestrowane godziny w danym okresie
- ✅ Generuje listy płac dla każdego z nich
- ✅ Pomija pracowników, dla których lista płac już istnieje (zapobiega duplikatom)
- ✅ Wyświetla podsumowanie: ile wygenerowano, ile pominięto, ewentualne błędy

**Statusy List Płac:**
- **Szkic (Draft)** - lista wygenerowana, można dodawać zaliczki i korekty
- **Zatwierdzona (Approved)** - lista zatwierdzona do wypłaty
- **Wypłacona (Paid)** - lista już wypłacona

---

## 💼 Koszty Stałe i Zmienne

### Koszty Stałe Firmy

Koszty stałe to regularne wydatki firmy niezależne od projektów (np. czynsz, media, ubezpieczenia).

**Zarządzanie Szablonami Kosztów Stałych:**

1. Z Dashboard kliknij **"Koszty Stałe"**
2. Kliknij **"Dodaj Szablon"**
3. Wypełnij:
   - **Nazwa** (np. "Czynsz biura")
   - **Kwota** i **Waluta**
   - **Okres** (Miesięczny, Kwartalny, Roczny)
   - **Kategoria** (opcjonalnie)
4. Kliknij **"Zapisz"**

**Generowanie Wpisów z Szablonów:**

1. W widoku kosztów stałych kliknij **"Generuj Wpisy"**
2. Wybierz **Szablon** i **Miesiąc**
3. Kliknij **"Generuj"**

System automatycznie tworzy wpis kosztu stałego dla wybranego miesiąca na podstawie szablonu.

**Ręczne Dodawanie Wpisów:**

1. Kliknij **"Dodaj Wpis"**
2. Wybierz szablon lub wprowadź dane ręcznie
3. Wprowadź **Kwotę**, **Data od**, **Data do**
4. Kliknij **"Zapisz"**

### Koszty Zmienne Projektów

Koszty zmienne to wydatki bezpośrednio związane z realizacją konkretnego projektu (np. materiały, transport, zakwaterowanie).

**Dodawanie Kosztu Zmiennego:**

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. Przejdź do zakładki **"Koszty Zmienne"**
3. Kliknij **"Dodaj Koszt"**
4. Wypełnij:
   - **Kwota** i **Waluta**
   - **Data**
   - **Opis** (np. "Transport materiałów")
   - **Kategoria** (opcjonalnie)
5. Kliknij **"Zapisz"**

Koszty zmienne są uwzględniane w analizie rentowności projektu.

---

## 📊 Dashboard Finansowy - Analiza Rentowności

Dashboard finansowy pokazuje rentowność firmy i poszczególnych projektów.

### Dostęp do Dashboardu Finansowego

1. Z Dashboard kliknij **"Dashboard Finansowy"** (lub **"Rentowność"**)
2. Wybierz **Miesiąc** do analizy (użyj przycisków "Poprzedni Miesiąc" / "Następny Miesiąc")

### Co Pokazuje Dashboard:

**Podsumowanie Firma:**
- **Przychody** - suma przychodów ze wszystkich aktywnych projektów
- **Koszty Pracy** - koszty wynagrodzeń pracowników (z payroll)
- **Koszty Zmienne** - suma kosztów zmiennych wszystkich projektów
- **Całkowite Koszty** - suma kosztów pracy i zmiennych
- **Marża** - przychody minus koszty
- **Marża %** - procent marży (rentowność)

**Tabela Projektów:**
Dla każdego aktywnego projektu pokazuje:
- **Przychody** - obliczone na podstawie typu projektu i stawek
- **Koszty Pracy** - suma wynagrodzeń pracowników przypisanych do projektu
- **Koszty Zmienne** - suma kosztów zmiennych projektu
- **Marża** i **Marża %**
- **Liczba Pracowników**
- **Godziny** - szacowane vs rzeczywiste
- **Wykonanie Planu** - procent realizacji planu godzinowego

**Top Pracownicy:**
Lista 10 pracowników z najwyższymi przychodami w danym miesiącu.

**Najdłuższe Rotacje:**
Lista 10 pracowników z najdłuższymi aktywnymi rotacjami.

### Jak Obliczane są Przychody:

- **Projekty typu "Hourly"** - przychody = suma godzin × stawka godzinowa projektu
- **Projekty typu "Fixed"** - przychody = stała kwota projektu
- **Projekty typu "Revenue Share"** - przychody = procent od przychodów projektu

---

## 📦 Magazyn i Wydania Sprzętu

System zarządza sprzętem BHP i narzędziami wydawanymi pracownikom.

### Zarządzanie Sprzętem

1. Z Dashboard kliknij **"Magazyn"** (Equipment)
2. Kliknij **"Dodaj Sprzęt"**
3. Wypełnij:
   - **Nazwa** (np. "Kask ochronny", "Buty BHP")
   - **Kategoria** (np. "Ochrona", "Narzędzia")
   - **Ilość w magazynie**
   - **Minimalna ilość** (próg niskiego stanu)
   - **Jednostka** (szt, kg, m, etc.)
   - **Koszt jednostkowy** (opcjonalnie)
   - **Zwrotny** - czy sprzęt jest zwracany (checkbox)
4. Kliknij **"Zapisz"**

**Filtrowanie:**
- Po nazwie (wyszukiwanie)
- Po kategorii
- Po statusie (Niski stan / OK)

### Wydanie Sprzętu Pracownikowi

1. W widoku magazynu kliknij **"Wydania"** lub przejdź do profilu pracownika → zakładka **"Sprzęt"**
2. Kliknij **"Dodaj Wydanie"**
3. Wybierz:
   - **Sprzęt** z listy
   - **Pracownik**
   - **Ilość** (system sprawdza dostępność)
   - **Data wydania**
   - **Oczekiwana data zwrotu** (opcjonalnie)
   - **Przypisanie do projektu** (opcjonalnie)
   - **Uwagi**
4. Kliknij **"Zapisz"**

System automatycznie:
- ✅ Sprawdza dostępność sprzętu w magazynie
- ✅ Zmniejsza stan magazynu o wydaną ilość
- ✅ Tworzy rekord wydania ze statusem "Wydane"

### Zwrot Sprzętu

1. W widoku wydań znajdź wydanie do zwrotu
2. Kliknij **"Zwróć"**
3. Wybierz:
   - **Data zwrotu**
   - **Status zwrotu:**
     - **Zwrócone** - sprzęt wrócił do magazynu (zwiększa stan)
     - **Uszkodzone** - sprzęt uszkodzony (nie wraca do magazynu)
     - **Zgubione** - sprzęt zgubiony (nie wraca do magazynu)
4. Kliknij **"Zapisz"**

**Wymagania Sprzętu dla Ról:**
Możesz zdefiniować, które role wymagają jakiego sprzętu:
1. W widoku sprzętu kliknij **"Wymagania"**
2. Dodaj rolę i wymaganą ilość
3. System może automatycznie przypominać o wydaniu wymaganego sprzętu

---

## 🚗 Wyjazdy i Powroty (Logistyka)

System zarządza wyjazdami pracowników do projektów i ich powrotami do bazy.

### Wyjazdy (Departures)

Wyjazdy to operacje logistyczne, które przenoszą pracowników z bazy do lokalizacji projektu.

**Dodawanie Wyjazdu:**

1. Z Dashboard kliknij **"Wyjazdy"** (lub z widoku tygodniowego)
2. Kliknij **"Dodaj Wyjazd"**
3. Wypełnij:
   - **Data wyjazdu**
   - **Lokalizacja docelowa** (gdzie jadą pracownicy)
   - **Pracownicy** (można wybrać wielu)
   - **Pojazd** (opcjonalnie)
   - **Uwagi**
4. Kliknij **"Zapisz"**

**Statusy Wyjazdów:**
- **Zaplanowane** - wyjazd zaplanowany, można edytować i anulować
- **Zrealizowane** - wyjazd zrealizowany
- **Anulowane** - wyjazd anulowany (nie można edytować)

**Anulowanie Wyjazdu:**

1. W widoku wyjazdu kliknij **"Anuluj"**
2. Potwierdź anulowanie

**Uwaga:** Można anulować tylko wyjazdy ze statusem "Zaplanowane".

### Powroty (Return Trips / Zjazdy)

Powroty to operacje logistyczne, które przywożą pracowników z projektów z powrotem do bazy.

**Dodawanie Powrotu:**

1. Z Dashboard kliknij **"Powroty"** (lub z widoku tygodniowego)
2. Kliknij **"Dodaj Powrót"**
3. Wypełnij:
   - **Data powrotu**
   - **Pracownicy** (można wybrać wielu)
   - **Pojazd powrotny** (opcjonalnie)
   - **Uwagi**
4. Kliknij **"Przygotuj"** (preview) lub **"Zapisz"** (bezpośrednio)

**System automatycznie:**

✅ **Skraca przypisania** - ustawia `end_date` wszystkich aktywnych przypisań pracowników na datę powrotu
- Przypisania do projektów (ProjectAssignment)
- Przypisania pojazdów (VehicleAssignment)
- Przypisania mieszkań (AccommodationAssignment)

✅ **Zapisuje oryginalne daty** - przed skróceniem zapisuje oryginalne `end_date` w tabeli uczestników (LogisticsEventParticipant)

✅ **Tworzy przypisanie pojazdu powrotnego** - jeśli wybrano pojazd, tworzy nowe przypisanie pojazdu dla pracowników na trasie powrotnej

✅ **Aktualizuje lokalizację pojazdu** - jeśli pojazd był przypisany, aktualizuje jego lokalizację na bazę

**Anulowanie Powrotu:**

1. W widoku powrotu kliknij **"Anuluj"**
2. System automatycznie:
   - ✅ **Przywraca oryginalne daty** - przywraca `end_date` wszystkich skróconych przypisań do oryginalnych wartości
   - ✅ **Usuwa przypisania pojazdu powrotnego** - usuwa przypisania pojazdu utworzone dla powrotu
   - ✅ **Ustawia status na "Anulowane"**

**Uwaga:** Można anulować tylko powroty ze statusem "Planowany".

**Edycja Powrotu:**

1. W widoku powrotu kliknij **"Edytuj"**
2. System automatycznie:
   - ✅ **Cofa zmiany** - przywraca oryginalne daty przypisań
   - ✅ **Usuwa stare uczestnictwa** - usuwa uczestników z wydarzenia
3. Wprowadź nowe dane i kliknij **"Zapisz"**
4. System ponownie wykonuje operacje skracania przypisań z nowymi danymi

**Widok Tygodniowy - Wyjazdy i Powroty:**

W widoku tygodniowym zobaczysz:
- **Wyjazdy** - lista wyjazdów w danym tygodniu
- **Powroty** - lista powrotów w danym tygodniu
- Pracownicy bez przypisania do projektu (gotowi do wyjazdu)

---

## ⏰ Ewidencja Godzin Pracy - Widok Miesięczny

**Zaawansowany widok miesięczny** do wprowadzania i przeglądania godzin pracy pracowników z inteligentnym grupowaniem i masową edycją.

### Jak Działa Widok Miesięczny:

1. Z Dashboard kliknij **"Ewidencja Godzin"** → **"Widok Miesięczny"**
2. Wybierz **Miesiąc** do edycji (użyj przycisków "Poprzedni Miesiąc" / "Następny Miesiąc")

### Kluczowe Funkcje:

**🎯 Inteligentne Grupowanie po Projektach:**
- Tabela jest zorganizowana hierarchicznie: **Projekt → Pracownicy**
- Każdy projekt ma nagłówek z nazwą i lokalizacją
- Pod każdym projektem widzisz listę pracowników przypisanych do tego projektu
- **Zero bałaganu** - wszystko uporządkowane i łatwe do znalezienia

**🔍 Automatyczne Filtrowanie:**
- ✅ **Wyświetla tylko pracowników, którzy mieli przypisania w danym miesiącu**
- Pracownicy bez przypisań nie zaśmiecają widoku
- System automatycznie znajduje wszystkie aktywne przypisania

**👁️ Wizualne Oznaczenia:**
- **Pola edytowalne** (białe) - dni, w których pracownik był przypisany
- **Pola wyszarzone** (disabled) - dni, w których pracownik **nie był przypisany** (nie można wprowadzić godzin)
- **Weekendy** - oznaczone innym kolorem dla łatwej identyfikacji
- **Wypełnione godziny** - pola z wprowadzonymi godzinami są wyróżnione

**⚡ Bulk Update (Masowa Edycja):**
- Wprowadź godziny dla wielu pracowników i dni **jednym razem**
- Wypełnij pola godzin w tabeli
- Kliknij **"Zapisz"** - system zapisze wszystkie zmiany w jednej transakcji
- **Oszczędność czasu** - nie musisz zapisywać każdego dnia osobno

**🛡️ Inteligentna Walidacja:**
- System sprawdza, czy data jest w zakresie przypisania
- Nie można wprowadzić godzin dla dni, w których pracownik nie był przypisany
- Godziny są walidowane (np. maksymalna liczba godzin dziennie)

**👔 Dla Kierowników Projektów:**
- Kierownicy widzą tylko projekty, którymi zarządzają
- Dostęp przez **"Moje Projekty"** → **"Ewidencja Godzin Zespołu"**
- Te same funkcje co administratorzy, ale ograniczone do swoich projektów

---

## 👔 Kierownik Projektu - Specjalne Uprawnienia

Projekty mogą mieć przypisanych **kierowników** (użytkowników), którzy mają ograniczony zestaw uprawnień do zarządzania swoimi projektami.

### Przypisywanie Kierownika do Projektu:

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. W sekcji **"Kierownicy"** kliknij **"Dodaj Kierownika"**
3. Wybierz użytkownika z listy
4. Kliknij **"Zapisz"**

### Uprawnienia Kierownika:

Kierownik projektu może:

✅ **Wyświetlać podstawowe dane projektu:**
- Nazwa, opis, lokalizacja
- Zapotrzebowanie na role
- Status projektu

✅ **Widzieć pracowników w projekcie:**
- Lista wszystkich pracowników przypisanych do projektu
- Role pracowników w projekcie
- Okresy przypisań

✅ **Wpisywać godziny pracy:**
- Dostęp do widoku miesięcznego Time Logs dla swojego projektu
- Może wprowadzać i edytować godziny pracy pracowników
- Bulk update (masowa edycja) godzin

✅ **Oceniać pracowników:**
- Może tworzyć, edytować i usuwać oceny pracowników przypisanych do projektu
- Oceny są powiązane z konkretnym pracownikiem i projektem
- System sprawdza, czy pracownik jest przypisany do projektu kierownika

✅ **Zarządzać zadaniami projektowymi:**
- Może zmieniać status zadań (W toku, Zakończone, Anulowane)
- Może oznaczać zadania jako wykonane

**Ograniczenia:**
- ❌ Nie może edytować danych projektu (nazwa, opis, lokalizacja)
- ❌ Nie może dodawać/usuwać przypisań pracowników
- ❌ Nie może zarządzać zapotrzebowaniem
- ❌ Nie ma dostępu do innych projektów (tylko te, którymi zarządza)

### Widok "Moje Projekty":

Kierownicy mają dostęp do sekcji **"Moje Projekty"** w menu, która zawiera:
- **Lista projektów** - tylko projekty, którymi zarządza
- **Ewidencja Godzin Zespołu** - widok miesięczny godzin dla swoich projektów
- **Oceny Pracowników** - oceny pracowników z jego projektów
- **Zadania** - zadania z jego projektów

---

## 💵 Zaliczki i Korekty (Advances & Adjustments)

System umożliwia zarządzanie zaliczkami i korektami do list płac.

### Zaliczki (Advances)

Zaliczki to przedpłaty wypłacane pracownikom przed rozliczeniem listy płac.

**Dodawanie Zaliczki:**

1. Z Dashboard kliknij **"Zaliczki"**
2. Kliknij **"Dodaj Zaliczkę"**
3. Wybierz:
   - **Lista Płac** (payroll) - zaliczka jest przypisana do konkretnej listy płac
   - **Kwota** i **Waluta**
   - **Data** wypłaty
   - **Oprocentowanie** (opcjonalnie):
     - Zaznacz **"Oprocentowana"** jeśli zaliczka jest oprocentowana
     - Wprowadź **Stawkę oprocentowania** (np. 5% = 5.00)
4. Kliknij **"Zapisz"**

**Automatyczne Obliczanie:**
- System automatycznie oblicza **kwotę do odliczenia** (zaliczka + odsetki jeśli oprocentowana)
- Zaliczki są automatycznie uwzględniane w **adjustments_amount** listy płac
- Lista płac jest automatycznie przeliczana po dodaniu/edycji/usunięciu zaliczki

### Korekty (Adjustments)

Korekty to dodatkowe kwoty dodawane lub odejmowane od listy płac (np. premie, kary, inne dopłaty).

**Dodawanie Korekty:**

1. Z Dashboard kliknij **"Korekty"** (lub z widoku listy płac)
2. Kliknij **"Dodaj Korektę"**
3. Wybierz:
   - **Lista Płac** (payroll)
   - **Kwota** (dodatnia = dopłata, ujemna = odliczenie)
   - **Waluta**
   - **Data**
   - **Opis** (np. "Premia za jakość", "Kara za spóźnienie")
4. Kliknij **"Zapisz"**

**Automatyczne Obliczanie:**
- Korekty są automatycznie uwzględniane w **adjustments_amount** listy płac
- Lista płac jest automatycznie przeliczana po dodaniu/edycji/usunięciu korekty

**Statusy List Płac:**
- **Szkic (Draft)** - można dodawać/edytować/usunąć zaliczki i korekty
- **Zatwierdzona (Approved)** - lista zatwierdzona, korekty zablokowane
- **Wypłacona (Paid)** - lista wypłacona, korekty zablokowane

---

## 📋 Zadania Projektowe (Project Tasks)

System umożliwia zarządzanie zadaniami w projektach.

### Dodawanie Zadania:

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. Przejdź do zakładki **"Zadania"**
3. Kliknij **"Dodaj Zadanie"**
4. Wypełnij:
   - **Tytuł** zadania
   - **Opis** (opcjonalnie)
   - **Przypisany do** (pracownik z projektu)
   - **Termin** (opcjonalnie)
   - **Priorytet** (Niski, Średni, Wysoki)
5. Kliknij **"Zapisz"**

### Statusy Zadań:

- **Nowe** - zadanie utworzone, nie rozpoczęte
- **W toku** - zadanie w trakcie realizacji
- **Zakończone** - zadanie ukończone
- **Anulowane** - zadanie anulowane

### Zarządzanie Zadaniami:

- **Oznacz jako "W toku"** - zmienia status na "W toku"
- **Oznacz jako "Zakończone"** - zmienia status na "Zakończone"
- **Anuluj** - anuluje zadanie
- **Edytuj** - edytuje szczegóły zadania
- **Usuń** - usuwa zadanie

**Dla Kierowników:**
- Kierownicy mogą zmieniać statusy zadań w swoich projektach
- Mogą oznaczać zadania jako wykonane lub anulowane

---

## 📁 Pliki Projektowe (Project Files)

System umożliwia przechowywanie plików związanych z projektami.

### Dodawanie Pliku do Projektu:

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. Przejdź do zakładki **"Pliki"**
3. Kliknij **"Dodaj Plik"**
4. Wybierz plik z dysku
5. Wprowadź:
   - **Nazwa** (opcjonalnie - domyślnie nazwa pliku)
   - **Opis** (opcjonalnie)
6. Kliknij **"Zapisz"**

**Informacje o Pliku:**
- System automatycznie zapisuje:
  - Rozmiar pliku
  - Typ MIME
  - Kto przesłał plik
  - Data przesłania

**Pobieranie Plików:**
- Kliknij na nazwę pliku, aby go pobrać
- Pliki są przechowywane w bezpiecznym miejscu na serwerze

---

## 🔍 Filtrowanie i Wyszukiwanie

### Rotacje Pracowników

1. Kliknij **"Rotacje Pracowników"**
2. Użyj filtrów:
   - **Pracownik** - wybierz konkretnego pracownika
   - **Status** - Zaplanowana, Aktywna, Zakończona, Anulowana
   - **Data rozpoczęcia** - zakres dat
   - **Data zakończenia** - zakres dat
3. Kliknij **"Filtruj"** lub **"Wyczyść filtry"**

### Pracownicy

1. Kliknij **"Pracownicy"**
2. Użyj pola wyszukiwania do filtrowania po imieniu, nazwisku lub emailu
3. Sortuj klikając nagłówki kolumn

### Pojazdy i Mieszkania

- Podobnie jak pracownicy - użyj wyszukiwania i sortowania

---

## ⚠️ Ważne Informacje

### Walidacja Przypisań

System **automatycznie blokuje** przypisania, jeśli:
- Pracownik nie ma rotacji pokrywającej cały okres przypisania
- Pracownik nie ma wszystkich wymaganych dokumentów ważnych w tym okresie
- Pracownik jest już przypisany do innego projektu w tym czasie
- Projekt nie ma zapotrzebowania na daną rolę w tym okresie

### Statusy Rotacji

- **Zaplanowana** - rotacja zaczyna się w przyszłości
- **Aktywna** - rotacja trwa obecnie
- **Zakończona** - rotacja już się zakończyła
- **Anulowana** - rotacja została ręcznie anulowana

Status jest **automatycznie obliczany** na podstawie dat - nie musisz go ustawiać ręcznie (oprócz "Anulowana").

### Dokumenty

- **Okresowe** - mają datę ważności od-do
- **Bezokresowe** - ważne od daty wydania bez końca

System sprawdza ważność dokumentów przed przypisaniem pracownika do projektu.

---

## 🗺️ Zewnętrzne API – Lokalizacja i Trasy

Aplikacja korzysta z dwóch zewnętrznych serwisów do geolokalizacji i planowania tras.

### 1. Nominatim / OpenStreetMap — Geokodowanie adresów

| Parametr | Wartość |
| :--- | :--- |
| **Serwis** | [Nominatim (OpenStreetMap)](https://nominatim.openstreetmap.org) |
| **Zastosowanie** | Zamiana adresu tekstowego na współrzędne GPS (i odwrotnie); autouzupełnianie pól adresowych |
| **Klucz API** | ❌ Nie wymagany |
| **Limit** | 1 żądanie/sekundę (policy OpenStreetMap) |
| **Konfiguracja `.env`** | Brak – działa bez konfiguracji |

**Gdzie jest używany:**
- Edycja lokalizacji (`/locations/{id}/edit`) — przycisk "Szukaj miejsca" → autouzupełnianie + pobieranie współrzędnych
- Edycja akomodacji — jak wyżej
- `Step4RoutePlanning` — automatyczne geokodowanie akomodacji bez współrzędnych podczas planowania trasy

---

### 2. OpenRouteService — Planowanie tras i obliczanie odległości

| Parametr | Wartość |
| :--- | :--- |
| **Serwis** | [OpenRouteService](https://openrouteservice.org) |
| **Zastosowanie** | Obliczanie tras, odległości (km) i czasu przejazdu między punktami; planowanie kolejności przystanków w kroku 4 formularza wyjazdu |
| **Klucz API** | ✅ **Wymagany** (darmowy plan: 2 000 żądań/dzień) |
| **Rejestracja** | [https://openrouteservice.org/dev/#/signup](https://openrouteservice.org/dev/#/signup) |

**Jak skonfigurować:**

1. Zarejestruj się na [openrouteservice.org](https://openrouteservice.org/dev/#/signup)
2. Wejdź w panel → **Tokens** → **Create Token**
3. Skopiuj wygenerowany klucz
4. Dodaj do `.env`:

```env
OPENROUTESERVICE_API_KEY=twój_klucz_api_tutaj
OPENROUTESERVICE_BASE_URL=https://api.openrouteservice.org/v2
```

> 💡 **Uwaga:** Jeśli używasz Docker Sail, dodaj te zmienne do pliku `.env` w głównym katalogu projektu. Po dodaniu możesz potrzebować zrestartować kontenery: `./vendor/bin/sail restart`.

**Gdzie jest używany:**
- `Step4RoutePlanning` — obliczanie trasy baza → mieszkania w formacie wyjazdu V2
- `RoutePlanningService` — wyliczanie dystansów dom-projekt dla planu wyjazdu

> ⚠️ **Bez skonfigurowanego `OPENROUTESERVICE_API_KEY` krok 4 formularza wyjazdu (`/departures/create-v2`) nie będzie w stanie zaplanować trasy** — system wyświetli komunikat błędu, ale zapis wyjazdu nadal zadziała (dane trasy zostaną puste).

---

## 🛠️ Wymagania Techniczne

### Dla Docker (Zalecane)
*   Docker Desktop (Windows/Mac) lub Docker Engine (Linux)
*   Docker Compose

### Dla Lokalnego Uruchomienia
*   PHP >= 8.1
*   Composer
*   Node.js & npm
*   MySQL lub SQLite

---

## 🐳 Instalacja i Uruchomienie

### Szybki Start z Docker (Zalecane)

1. **Sklonuj repozytorium:**
   ```bash
   git clone https://github.com/KarolSzynkiewicz/delegacje.git
   cd delegacje
   ```

2. **Skopiuj plik środowiskowy:**
   ```bash
   cp .env.example .env
   ```

3. **Uruchom kontenery Docker:**
   ```bash
   ./vendor/bin/sail up -d
   ```

4. **Zainstaluj zależności (tylko przy pierwszym uruchomieniu):**
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

5. **Wygeneruj klucz aplikacji:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **Uruchom migracje i seedery:**
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```

7. **Otwórz aplikację w przeglądarce:**
   ```
   http://localhost
   ```

### Przydatne Komendy Sail

```bash
./vendor/bin/sail up -d              # Uruchom kontenery w tle
./vendor/bin/sail down               # Zatrzymaj kontenery
./vendor/bin/sail artisan ...        # Uruchom komendy Artisan
./vendor/bin/sail composer ...       # Uruchom komendy Composer
./vendor/bin/sail npm ...            # Uruchom komendy NPM
./vendor/bin/sail mysql              # Dostęp do MySQL CLI
./vendor/bin/sail shell              # Dostęp do bash w kontenerze
./vendor/bin/sail logs               # Zobacz logi kontenerów
```

### Naprawa Uprawnień Cache (Sail)

Jeśli wystąpi problem z cache (błąd `file_put_contents: Failed to open stream`):

```bash
./fix-cache-permissions.sh
```

Lub ręcznie:
```bash
./vendor/bin/sail exec laravel.test bash -c "mkdir -p /var/www/html/storage/framework/cache/data && chown -R sail:sail /var/www/html/storage/framework/cache && chmod -R 775 /var/www/html/storage/framework/cache"
```

---

## 💻 Uruchomienie Lokalne (Bez Docker)

### 1. Instalacja

1. **Sklonuj repozytorium:**
   ```bash
   git clone https://github.com/KarolSzynkiewicz/delegacje.git
   cd delegacje
   ```

2. **Zainstaluj zależności PHP:**
   ```bash
   composer install
   ```

3. **Skonfiguruj środowisko:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Skonfiguruj bazę danych w `.env`:**
   
   **Dla SQLite (prostsze):**
   ```env
   DB_CONNECTION=sqlite
   ```
   Następnie utwórz plik bazy:
   ```bash
   touch database/database.sqlite
   ```
   
   **Dla MySQL:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Zainstaluj zależności front-end:**
   ```bash
   npm install
   npm run build
   ```

6. **Uruchom migracje i seedery:**
   ```bash
   php artisan migrate --seed
   ```

7. **Uruchom serwer deweloperski:**
   ```bash
   php artisan serve
   ```

8. **Aplikacja dostępna pod adresem:**
   ```
   http://127.0.0.1:8000
   ```

---

## 🔑 Dane Logowania (Testowe)

Po uruchomieniu migracji z seederami (`migrate --seed`), możesz zalogować się:

| Pole | Wartość |
| :--- | :--- |
| **Email** | `test@example.com` |
| **Hasło** | `password123` |

---

## 📊 Moduł Raportów

Moduł raportowania jest w fazie rozwoju.

**Planowane funkcjonalności:**
- Podsumowanie delegacji
- Godziny pracy pracowników
- Status projektów
- Eksport do PDF/Excel

---

## 🧪 Testowanie

```bash
# Z Docker
./vendor/bin/sail artisan test

# Lokalnie
php artisan test
```

---

## 📝 Struktura Projektu

```
delegacje/
├── app/                    # Logika aplikacji (Controllers, Models, Services)
│   ├── Http/
│   │   ├── Controllers/   # Kontrolery
│   │   └── Requests/      # Form Requests (walidacja danych wejściowych)
│   ├── Models/            # Modele Eloquent
│   ├── Services/          # Logika biznesowa i walidacja
│   └── Traits/            # Traity (wspólne funkcjonalności)
├── database/              # Migracje, seedery, factory
├── resources/             # Widoki Blade, CSS, JS
├── routes/                # Definicje tras
├── public/                # Publiczne pliki (index.php, assets)
├── vendor/                # Zależności Composer
├── docker-compose.yml     # Konfiguracja Docker Sail
├── .env.example           # Przykładowy plik środowiskowy
└── README.md             # Ten plik
```

---

## 🏗️ Architektura i Konwencje

### 1. Kontrakty (Contracts)
**Gdzie:** `app/Contracts/`
**Kiedy używać:**
- Polimorficzne relacje (HasEmployee, HasDateRange)
- Read-models / Query services
- Gdzie naprawdę potrzebujesz polimorfizmu

**NIE używaj:**
- Gdy masz konkretny typ - typuj konkretnie
- Nigdy razem z instanceof

### 2. Traity (Traits)
**Gdzie:** `app/Traits/`
**Kiedy używać:**
- Wspólna logika powtarzająca się w wielu klasach
- Częste operacje: overlap dat, walidacja start_date < end_date
- Przykład: `HasDateRange` trait dla operacji na zakresach dat

### 3. Modele (Models)
**Konwencja nazewnictwa pól dat:**
- ZAWSZE: `start_date` / `end_date` (nie date_from/date_to/issued_date/returned_date)
- Zgodnie z konwencją od poziomu bazy danych
- Użyj trait `HasDateRange` dla spójnej obsługi

### 4. Migracje (Migrations)
**Konwencja:**
```php
$table->date('start_date');
$table->date('end_date')->nullable();
```
- ZAWSZE `start_date` / `end_date`
- Spójnie we wszystkich tabelach

### 5. Kontrolery (Controllers)
**Zasady:**
- CIENKIE - tylko orkiestracja
- Przekazują logikę biznesową do serwisów
- Przekazują CAŁE OBIEKTY, nie ID
- Używają route model binding
- Robią findOrFail (nie serwisy)

### 6. Serwisy (Services)
**Zasady:**
- NIE robią findOrFail
- NIE pytają bazy danych (dostają obiekty)
- Liczą / sprawdzają / wykonują logikę biznesową
- Używają Eloquent (scopes, relationships)
- Używają Carbona - operują na obiektach
- Przyjmują JAWNE ARGUMENTY, nie array $data

### 7. Traity w Serwisach
- Centralizują tę samą logikę w różnych serwisach
- Częste operacje: overlap dat, walidacja dat

### 8. Kontrakty w Serwisach
- Serwisy implementują kontrakty
- Zapewniają spójne nazewnictwo + przejrzystość
- Definiują kontrakt API serwisu

### Warstwy Aplikacji

1. **Form Requests** - Walidacja danych wejściowych (required, date, exists, etc.)
2. **Services** - Cała logika biznesowa i walidacja (role, availability, overlaps, etc.)
3. **Models** - Metody pomocnicze (hasRole, isAvailable, etc.) - sprawdzanie stanu
4. **Controllers** - Orkiestracja, wywołanie serwisów, zwracanie odpowiedzi

### Zasady

- **DRY (Don't Repeat Yourself)** - Logika biznesowa w serwisach, nie duplikowana
- **Single Responsibility** - Każda klasa ma jedną odpowiedzialność
- **Separation of Concerns** - Form Requests dla walidacji, Services dla logiki, Controllers dla orkiestracji
- **No Repository Pattern** - Używamy Eloquent bezpośrednio + scopes + query services
- **No Overengineering** - Kontrakty tylko tam, gdzie naprawdę potrzebne (polimorfizm, read-models)

---

## 🤝 Wkład w Projekt

1. Fork projektu
2. Utwórz branch dla nowej funkcjonalności (`git checkout -b feature/AmazingFeature`)
3. Commit zmian (`git commit -m 'Add some AmazingFeature'`)
4. Push do brancha (`git push origin feature/AmazingFeature`)
5. Otwórz Pull Request

---

## 📄 Licencja

Projekt stworzony dla celów demonstracyjnych i edukacyjnych.

---

## 🆘 Wsparcie

Jeśli napotkasz problemy:
1. Sprawdź sekcję **Instrukcje dla Użytkownika** powyżej
2. Sprawdź dokumentację Docker dla problemów z Docker
3. Otwórz Issue na GitHub

---

**Rekomendowane:** Użyj Docker z Laravel Sail dla najlepszego doświadczenia deweloperskiego! 🚢

---

## 🌟 Dlaczego Stocznia to Najlepsze Rozwiązanie?

### 💼 Kompleksowość
**Wszystko w jednym miejscu** - od planowania projektów, przez zarządzanie pracownikami, aż po generowanie list płac i analizę rentowności. Nie potrzebujesz wielu systemów - Stocznia obsługuje cały proces biznesowy.

### 🎯 Inteligentna Automatyzacja
- **Automatyczne generowanie list płac** dla wszystkich pracowników jednym kliknięciem
- **Automatyczne czyszczenie przypisań** przy wyjazdach i powrotach
- **Automatyczne sprawdzanie dostępności** pracowników przed przypisaniem
- **Automatyczne generowanie uprawnień** z route - zero konfiguracji

### 📊 Zaawansowana Analityka
- **Dashboard finansowy** z analizą rentowności projektów i firmy
- **Widok miesięczny godzin** z grupowaniem po projektach
- **Top pracownicy** według przychodów
- **Najdłuższe rotacje** - identyfikacja kluczowych pracowników

### 🔐 Bezpieczeństwo i Elastyczność
- **Pełny RBAC** - dynamiczna tabela uprawnień dostosowana do potrzeb
- **Kierownicy projektów** - dedykowane uprawnienia dla zarządzania zespołem
- **Walidacja na każdym kroku** - system zapobiega błędom przed ich wystąpieniem

### ⚡ Wydajność
- **Cache'owanie** - uprawnienia (24h), route (1h), menu (1h)
- **Optymalizacja zapytań** - eager loading, brak N+1 queries
- **Szybkie działanie** nawet przy dużych ilościach danych

### 🎨 Intuicyjny Interfejs
- **Widok tygodniowy** - główny hub zarządzania
- **Widok miesięczny godzin** - zaawansowany, ale prosty w użyciu
- **Skróty klawiszowe** - Ctrl+Strzałka do szybkiej nawigacji
- **Responsywny design** - działa na wszystkich urządzeniach

### 🚀 Skalowalność
- **Nowe route = automatycznie w tabelce uprawnień**
- **Nowe projekty = automatycznie w widoku tygodniowym**
- **Nowi pracownicy = automatycznie w systemie**
- **Zero konfiguracji** - system adaptuje się do zmian

---

**Stocznia to nie tylko system - to kompletne rozwiązanie ERP dla firm delegujących pracowników.** 🎯
