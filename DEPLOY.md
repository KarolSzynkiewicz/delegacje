# 🚀 Instrukcja Deploymentu - Laravel na Railway

Ten przewodnik pomoże Ci wdrożyć aplikację Laravel na **Railway** z automatycznym HTTPS (bez potrzeby posiadania domeny).

## 📋 Wymagania

- Konto na GitHub (darmowe)
- Konto na Railway (darmowe - 500h/miesiąc)
- Repozytorium GitHub z Twoim kodem

---

## 🎯 Opcja 1: Railway (Zalecane - Najłatwiejsze)

### Krok 1: Przygotowanie repozytorium

1. **Upewnij się, że masz wszystkie pliki w repozytorium:**
   ```bash
   git add .
   git commit -m "Add deployment configuration"
   git push origin main
   ```

### Krok 2: Utworzenie konta na Railway

1. Przejdź na [railway.app](https://railway.app)
2. Kliknij **"Start a New Project"**
3. Zaloguj się przez **GitHub** (najłatwiej)
4. Zaakceptuj uprawnienia

### Krok 3: Nowy projekt na Railway

1. Kliknij **"New Project"**
2. Wybierz **"Deploy from GitHub repo"**
3. Wybierz swoje repozytorium `delegacje`
4. Railway automatycznie wykryje `Dockerfile` i zacznie budować

### Krok 4: Konfiguracja zmiennych środowiskowych

1. W projekcie Railway kliknij na **"Variables"** (lub **"Settings"** → **"Variables"**)
2. Dodaj następujące zmienne:

   ```
   APP_NAME=Delegacje
   APP_ENV=production
   APP_KEY=base64:WYGENERUJ_KLUCZ_PONIZEJ
   APP_DEBUG=false
   APP_URL=https://twoja-aplikacja.up.railway.app
   
   DB_CONNECTION=mysql
   DB_HOST=containers-us-west-XXX.railway.app
   DB_PORT=3306
   DB_DATABASE=railway
   DB_USERNAME=root
   DB_PASSWORD=haslo_z_railway
   ```

3. **Wygeneruj APP_KEY:**
   ```bash
   php artisan key:generate --show
   ```
   Skopiuj wygenerowany klucz i wklej jako `APP_KEY` w Railway.

4. **Dodaj bazę danych MySQL:**
   - W projekcie Railway kliknij **"New"** → **"Database"** → **"MySQL"**
   - Railway automatycznie utworzy bazę i doda zmienne `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - Skopiuj te wartości do zmiennych środowiskowych

### Krok 5: Konfiguracja domeny (opcjonalne)

Railway automatycznie przypisuje domenę HTTPS:
- Format: `https://twoja-aplikacja.up.railway.app`
- **HTTPS działa automatycznie** - nie musisz nic konfigurować!

Jeśli chcesz własną domenę:
1. W projekcie Railway → **"Settings"** → **"Domains"**
2. Dodaj swoją domenę
3. Railway automatycznie skonfiguruje HTTPS przez Let's Encrypt

### Krok 6: Deploy

1. Railway automatycznie rozpocznie build po połączeniu z GitHub
2. Możesz obserwować logi w zakładce **"Deployments"**
3. Po zakończeniu builda, aplikacja będzie dostępna pod adresem HTTPS

### Krok 7: Uruchomienie migracji

Po pierwszym deployu, musisz uruchomić migracje:

1. W Railway kliknij na serwis aplikacji
2. Przejdź do zakładki **"Deployments"**
3. Kliknij na najnowszy deployment
4. Otwórz **"View Logs"**
5. Kliknij **"Run Command"** (lub użyj terminala)
6. Wpisz:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

### Krok 8: Sprawdzenie działania

1. Otwórz adres URL z Railway (np. `https://twoja-aplikacja.up.railway.app`)
2. Powinieneś zobaczyć stronę logowania Laravel
3. Zaloguj się używając:
   - Email: `test@example.com`
   - Hasło: `password123`

---

## 🔄 Automatyczny Deploy (CI/CD)

Railway automatycznie deployuje każdy push do głównego brancha (main/master).

**Jak to działa:**
1. Push do GitHub → Railway automatycznie wykrywa zmiany
2. Railway buduje nowy obraz Docker
3. Railway deployuje nową wersję
4. Zero downtime - nowa wersja zastępuje starą

**Aby wyłączyć auto-deploy:**
- W Railway → **"Settings"** → **"Source"** → wyłącz **"Auto Deploy"**

---

## 🛠️ Alternatywa: Render.com

Jeśli Railway nie działa, możesz użyć **Render.com**:

### Krok 1: Utworzenie konta
1. Przejdź na [render.com](https://render.com)
2. Zaloguj się przez GitHub

### Krok 2: Nowy Web Service
1. Kliknij **"New +"** → **"Web Service"**
2. Połącz repozytorium GitHub
3. Ustaw:
   - **Name:** delegacje
   - **Environment:** Docker
   - **Region:** Frankfurt (najbliżej Polski)
   - **Branch:** main
   - **Root Directory:** (zostaw puste)

### Krok 3: Zmienne środowiskowe
Dodaj te same zmienne co w Railway (patrz Krok 4 powyżej)

### Krok 4: Dodaj bazę danych
1. **"New +"** → **"PostgreSQL"** (lub MySQL jeśli dostępne)
2. Skopiuj dane połączenia do zmiennych środowiskowych

### Krok 5: Deploy
Render automatycznie zbuduje i wdroży aplikację.

---

## 🔧 Rozwiązywanie problemów

### Problem: Błąd "APP_KEY not set"
**Rozwiązanie:** Upewnij się, że dodałeś `APP_KEY` w zmiennych środowiskowych Railway.

### Problem: Błąd połączenia z bazą danych
**Rozwiązanie:** 
- Sprawdź czy dodałeś bazę danych MySQL w Railway
- Skopiuj dokładnie wartości `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` z bazy danych Railway

### Problem: Błąd 500 po deployu
**Rozwiązanie:**
1. Sprawdź logi w Railway → **"Deployments"** → **"View Logs"**
2. Uruchom migracje: `php artisan migrate --force`
3. Sprawdź uprawnienia: `php artisan storage:link`

### Problem: Assets nie ładują się (brak CSS/JS)
**Rozwiązanie:**
- Upewnij się, że w Dockerfile jest `npm run build`
- Sprawdź czy pliki w `public/build` są dostępne

### Problem: Błąd "Storage link not found"
**Rozwiązanie:**
Uruchom w terminalu Railway:
```bash
php artisan storage:link
```

---

## 📊 Monitorowanie

### Logi
- Railway: **"Deployments"** → wybierz deployment → **"View Logs"**
- Możesz też użyć: `railway logs` (jeśli masz CLI)

### Metryki
- Railway pokazuje użycie CPU, RAM, sieci w czasie rzeczywistym
- Darmowy tier: 500 godzin/miesiąc, $5 kredytu

---

## 🔐 Bezpieczeństwo

### Zmienne środowiskowe
**NIGDY nie commituj `.env` do Git!**

Zmienne do ustawienia w Railway:
- ✅ `APP_KEY` - wygeneruj przez `php artisan key:generate`
- ✅ `DB_PASSWORD` - skopiuj z Railway Database
- ✅ `APP_DEBUG=false` - w produkcji zawsze false
- ✅ `APP_ENV=production`

### HTTPS
- Railway automatycznie zapewnia HTTPS
- Certyfikaty są automatycznie odnawiane
- Nie musisz nic konfigurować!

---

## 💰 Koszty

### Railway (Darmowy tier)
- **500 godzin/miesiąc** darmowo
- **$5 kredytu** miesięcznie
- Wystarczy dla małych/średnich aplikacji

### Render (Darmowy tier)
- **750 godzin/miesiąc** darmowo
- Wolniejszy start (cold start ~30s)
- Dobry dla projektów testowych

---

## 🚀 Szybki Start (TL;DR)

1. **Push do GitHub:**
   ```bash
   git add .
   git commit -m "Ready for deployment"
   git push origin main
   ```

2. **Railway:**
   - Zaloguj się przez GitHub
   - New Project → Deploy from GitHub
   - Wybierz repozytorium
   - Dodaj MySQL Database
   - Skopiuj zmienne DB do Variables
   - Dodaj `APP_KEY` (wygeneruj przez `php artisan key:generate`)
   - Ustaw `APP_URL` na URL z Railway
   - Po deployu: `php artisan migrate --force`

3. **Gotowe!** Aplikacja działa pod HTTPS 🎉

---

## 📞 Wsparcie

Jeśli masz problemy:
1. Sprawdź logi w Railway
2. Sprawdź sekcję "Rozwiązywanie problemów" powyżej
3. Dokumentacja Railway: [docs.railway.app](https://docs.railway.app)

---

**Powodzenia z deploymentem! 🚀**
