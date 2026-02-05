# Deployment Guide - Railway.app

## Pełna historia deploymentu - od zera do działającej aplikacji

### Wprowadzenie

Ten dokument opisuje kompletny proces deploymentu aplikacji Laravel na Railway.app, od początkowych problemów do finalnego działającego rozwiązania. Dokumentacja zawiera wszystkie napotkane problemy, próby ich rozwiązania oraz finalne rozwiązania.

---

## Faza 1: Początkowy setup i pierwsze problemy

### Problem 1: Composer install z package:discover

**Symptom:** 
```
Script @php artisan package:discover --ansi handling the post-autoload-dump event returned with error code 1
```

**Przyczyna:** 
- Composer próbował uruchomić `php artisan package:discover` podczas `composer install`
- Laravel wymaga `.env` i podstawowej konfiguracji do działania
- W czasie builda nie było jeszcze pełnej konfiguracji

**Próby naprawy:**
1. Użycie `--no-scripts` w `composer install`
2. Ręczne uruchomienie `php artisan package:discover` po kopiowaniu plików
3. Tworzenie minimalnego `.env` przed `composer install`
4. Tworzenie katalogów cache przed `composer install`

**Rozwiązanie:** 
Kopiowanie wszystkich plików aplikacji PRZED `composer install --no-scripts`, potem ręczne uruchomienie `package:discover` po skopiowaniu plików i utworzeniu podstawowego `.env`.

---

### Problem 2: PDO MySQL driver missing

**Symptom:** 
```
could not find driver (Connection: mysql)
```

**Przyczyna:** 
- PHP extension `pdo_mysql` nie była włączona w production stage Dockerfile

**Rozwiązanie:** 
Dodanie `docker-php-ext-enable pdo_mysql` w Dockerfile production stage.

---

## Faza 2: Architektura kontenera - Nginx vs Supervisor

### Problem 3: Supervisor vs Nginx jako główny proces

**Początkowy setup:**
- Supervisor zarządzał Nginx + PHP-FPM
- Supervisor był PID 1

**Problem:** 
Railway wymaga, żeby kontener nie zakończył się i żeby na zadeklarowanym porcie pojawiła się odpowiedź HTTP.

**WAŻNE:** 
Railway NIE wymaga, żeby PID 1 był serwerem HTTP. Supervisor może być PID 1 i działać poprawnie, jeśli:
- Nie forkuje w sposób kończący PID 1
- Nie uruchamia procesów w tle bez exec
- Nie kończy się po starcie childrenów

**Próby naprawy:**
1. Przełączenie z Unix socket na TCP (127.0.0.1:9000) dla PHP-FPM
2. Zmiana uprawnień (nginx:nginx vs www-data:www-data)
3. Przełączenie z Alpine na Ubuntu (prostsze uprawnienia)

**Rozwiązanie:** 
Usunięcie Supervisora, Nginx jako PID 1, PHP-FPM w tle. To była rozsądna decyzja, ale nie jedyna możliwa.

---

### Problem 4: Dynamic PORT handling

**Symptom:** 
```
invalid port in "${PORT:-80}" - Nginx nie interpretował zmiennej środowiskowej
```

**Przyczyna:** 
- Railway przypisuje losowy port przez zmienną środowiskową `PORT`
- Nginx nie interpretuje zmiennych środowiskowych w konfiguracji bezpośrednio
- Konfiguracja Nginx była statyczna

**Próby naprawy:**
1. `sed` do podmiany PORT
2. `envsubst` (wymaga `gettext-base`)
3. Różne podejścia do escape'owania znaków specjalnych

**Rozwiązanie:** 
Użycie `envsubst '${PORT}'` w entrypoint przed startem Nginx. `envsubst` podmienia zmienne środowiskowe w plikach tekstowych.

---

### Problem 5: Przeinżynierowany entrypoint

**Symptom:** 
- Entrypoint robił za dużo (migracje, cache, setup)
- Używał `ps` (nie było `procps` w obrazie)
- Uruchamiał Nginx wielokrotnie
- PID 1 kończył się → Railway zabijał kontener

**Błędy w entrypoint:**
- ❌ Migracje w entrypoint (NIE WOLNO - powinny być osobno)
- ❌ `storage:link` przy każdym starcie (NIE WOLNO - powinno być w build/CI)
- ❌ Cache clear/cache przy każdym starcie (NIE WOLNO - powinno być w build/CI)
- ❌ Sprawdzanie bazy danych w pętli (NIE WOLNO - to nie jest odpowiedzialność runtime)
- ❌ Użycie `ps` bez zainstalowanego pakietu (NIE WOLNO - użyj `/proc/1/status`, `ss`, `pgrep`)
- ❌ Uruchamianie Nginx w tle (`&`) zamiast `exec` (NIE WOLNO - PID 1 musi być głównym procesem)

**Główny problem:** 
Zbyt wiele odpowiedzialności wrzucone do runtime'u. Runtime ma serwować HTTP. Wszystko inne to procesy przed lub obok.

**Rozwiązanie:** 
Minimalny entrypoint:
```bash
#!/bin/sh
# Substitute PORT
export PORT=${PORT:-80}
envsubst '${PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Start PHP-FPM in background
php-fpm -D

# Execute Nginx as main process (PID 1)
exec nginx -g 'daemon off;'
```

---

### Problem 6: envsubst missing (gettext-base)

**Symptom:** 
```
/usr/local/bin/entrypoint.sh: 7: envsubst: not found
```

**Przyczyna:** 
Entrypoint używał `envsubst` do podmiany PORT, ale pakiet `gettext-base` nie był zainstalowany w obrazie.

**Rozwiązanie:** 
Dodanie `gettext-base` do Dockerfile w sekcji runtime dependencies.

---

## Faza 3: Migracje bazy danych

### Problem 7: Migracja bazy danych - payroll_id NOT NULL

**Symptom:** 
```
SQLSTATE[HY000]: General error: 1830 Column 'payroll_id' cannot be NOT NULL
```

**Przyczyna:** 
Migracja próbuje ustawić `payroll_id` jako NOT NULL, ale istnieją foreign key constraints i możliwe NULL wartości w istniejących rekordach.

**BŁĄD KRYTYCZNY:** 
Ignorowanie błędu migracji = brak gwarancji spójności aplikacji.

**ZAKAZ:** 
Kontener produkcyjny NIE POWINIEN się uruchomić, jeśli migracje nie przeszły. Inaczej aplikacja działa na niespójnym schemacie bazy danych.

**Rozwiązanie:** 
Naprawienie migracji przez:
1. Wyłączenie foreign key constraints przed zmianą
2. Usunięcie rekordów z NULL payroll_id lub ustawienie wartości domyślnych
3. Dynamiczne znalezienie i usunięcie foreign key constraints
4. Zmiana kolumny na NOT NULL
5. Ponowne dodanie foreign key constraints
6. Włączenie foreign key constraints

---

### Problem 8: Inne problemy z migracjami

**Problem 8a: Foreign key do nieistniejącej tabeli**
- Migracja próbowała utworzyć foreign key do `user_roles` przed utworzeniem tabeli przez Spatie Permission
- **Rozwiązanie:** Dodanie sprawdzenia `Schema::hasTable('user_roles')` przed dodaniem foreign key

**Problem 8b: Zbyt długie nazwy indeksów**
- MySQL ma limit 64 znaków na nazwę indeksu
- **Rozwiązanie:** Skrócenie nazwy indeksu przez jawne zdefiniowanie

**Problem 8c: Duplikacja kolumn w migracjach**
- Migracja próbowała dodać kolumny, które już istniały
- **Rozwiązanie:** Dodanie sprawdzenia `Schema::hasColumn()` przed dodaniem kolumn

---

## Faza 4: Architektura - przejście z Nginx na php artisan serve

### Problem 9: Nginx + PHP-FPM - złożoność i problemy

**Symptom:** 
- 502 Bad Gateway mimo działającego Nginx
- Problemy z konfiguracją fastcgi_pass
- Trudności w debugowaniu komunikacji Nginx ↔ PHP-FPM

**Analiza:** 
Użytkownik zasugerował, że kontener z Nginx + PHP-FPM jest "architektonicznie niezgodny z Railway" i zalecił użycie `php artisan serve`.

**Rozwiązanie:** 
Przejście na `php artisan serve`:
- Usunięcie Nginx z Dockerfile
- Usunięcie PHP-FPM z Dockerfile
- Użycie `php artisan serve --host=0.0.0.0 --port=$PORT` jako głównego procesu
- Uproszczenie architektury do jednego procesu

**Zmiany:**
- Dockerfile: `FROM php:8.3-cli` zamiast `php:8.3-fpm`
- Entrypoint: `exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"`
- Usunięcie wszystkich konfiguracji Nginx i PHP-FPM

---

## Faza 5: APP_KEY i cache

### Problem 10: APP_KEY nie jest ustawiony

**Symptom:** 
```
No application encryption key has been specified.
```

**Przyczyna:** 
- `APP_KEY` nie był ustawiony w Railway Variables
- Laravel wymaga `APP_KEY` do szyfrowania sesji, cookies, itp.

**Rozwiązanie:** 
1. Wygenerowanie klucza lokalnie: `php artisan key:generate --show`
2. Dodanie `APP_KEY=base64:xxxxx` do Railway Variables
3. Weryfikacja w entrypoint, że `APP_KEY` jest ustawiony przed startem

---

### Problem 11: Cache blokuje zmienne środowiskowe

**Symptom:** 
- Zmiany w Railway Variables nie były widoczne w aplikacji
- Laravel używał cache'owanych wartości zamiast zmiennych środowiskowych

**Przyczyna:** 
- `php artisan config:cache` tworzy cache konfiguracji
- Cache blokuje odczyt zmiennych środowiskowych
- W Railway zmienne środowiskowe mogą się zmieniać bez rebuild

**Rozwiązanie:** 
1. Usunięcie `config:cache` z entrypoint
2. Dodanie `config:clear` na początku entrypoint
3. Dodanie `route:clear`, `view:clear`, `cache:clear` przed startem
4. **ZASADA:** Nie cache'ować konfiguracji w kontenerze, jeśli zmienne środowiskowe mogą się zmieniać

---

### Problem 12: .env file vs Railway Variables

**Symptom:** 
- `.env` file w kontenerze zawierał stary `APP_KEY` z build time
- Railway Variables miały nowy `APP_KEY`
- Laravel preferował `.env` file nad zmienne środowiskowe

**Rozwiązanie:** 
Usunięcie `.env` file w entrypoint przed startem, aby Laravel używał tylko Railway Variables:
```bash
if [ -f .env ]; then
    rm -f .env
    echo "✅ .env removed - Laravel will use Railway env vars only"
fi
```

---

## Faza 6: Healthcheck i build cache

### Problem 13: Healthcheck failed

**Symptom:** 
```
Healthcheck failed!
Attempt #1 failed with service unavailable
```

**Przyczyna:** 
- Healthcheck endpoint `/api/health` nie działał
- Aplikacja nie odpowiadała na healthcheck przed pełnym startem

**Rozwiązanie:** 
1. Utworzenie prostego endpoint `/api/health` zwracającego JSON
2. Wyłączenie middleware wymagających APP_KEY dla healthcheck
3. Zwiększenie `healthcheckTimeout` w `railway.json` do 5000ms
4. Dodanie prostego JSON response w root route `/` jako fallback

---

### Problem 14: Docker build cache

**Symptom:** 
- Zmiany w Dockerfile nie były widoczne w buildzie
- Railway używał starych cache'owanych warstw

**Próby naprawy:**
1. Dodanie `ARG CACHEBUST=1` do Dockerfile
2. Dodanie `RUN echo "Build timestamp: $(date)"` do wymuszenia rebuild
3. Zmiana `CACHEBUST` w Railway Variables

**Rozwiązanie:** 
Dodanie `ARG CACHEBUST` i użycie go w `RUN` command, aby wymusić rebuild warstwy:
```dockerfile
ARG CACHEBUST=1
RUN echo "Build cache bust: ${CACHEBUST}" > /tmp/entrypoint-build.txt
```

---

## Faza 7: CSS i frontend assets

### Problem 15: CSS nie działa - Mixed Content

**Symptom:** 
```
Mixed Content: The page at 'https://...' was loaded over HTTPS, but requested an insecure stylesheet 'http://...'
```

**Przyczyna:** 
- Plik `public/hot` istniał w kontenerze
- Laravel Vite myślał, że jest w trybie HMR (Hot Module Replacement)
- W trybie HMR generował URL-e do `http://localhost:5174` zamiast używać `ASSET_URL`

**Rozwiązanie:** 
1. Usunięcie pliku `public/hot` z kontenera
2. Dodanie `public/hot` do `.gitignore`
3. Laravel Vite przestał być w trybie HMR i zaczął używać production build z HTTPS

---

### Problem 16: npm build w Dockerfile

**Symptom:** 
- CSS działał na homepage, ale nie działał na innych stronach
- `npm run build` był w Dockerfile, ale assets nie były dostępne

**Przyczyna:** 
- `npm run build` generował pliki w `public/build/`
- `public/build` był w `.gitignore` (poprawnie)
- Build działał poprawnie, problem był w HMR mode (patrz Problem 15)

**Rozwiązanie:** 
Problem został rozwiązany przez usunięcie pliku `hot` (Problem 15). `npm run build` działał poprawnie.

---

### Problem 17: server.php dla static files

**Symptom:** 
- `php artisan serve` nie serwował poprawnie static files (CSS, JS)
- Plik `server.php` nie był dostępny

**Rozwiązanie:** 
Kopiowanie `server.php` do root i `public/` w Dockerfile:
```dockerfile
RUN cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php
```

---

## Faza 8: HTTPS i Mixed Content - globalne wymuszenie

### Problem 18: Formularze używają HTTP zamiast HTTPS

**Symptom:** 
```
Mixed Content: The page at 'https://...' contains a form that targets an insecure endpoint 'http://...'
```

**Przyczyna:** 
- Laravel generował URL-e na podstawie `request()->getScheme()`
- Mimo że Railway używa HTTPS, Laravel czasami generował HTTP
- Brak globalnego wymuszenia HTTPS

**Rozwiązanie:** 
Dodanie `URL::forceScheme('https')` w `AppServiceProvider`:
```php
public function boot(): void
{
    // Force HTTPS for all URLs in production (Railway uses HTTPS)
    if (config('app.env') === 'production' || request()->isSecure()) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
    // ...
}
```

---

## Finalna architektura

### Dockerfile

**Multi-stage build:**
1. **Build stage (`php:8.3-fpm`):**
   - Instalacja zależności build (git, curl, nodejs, npm)
   - Instalacja PHP extensions
   - `composer install --no-dev --optimize-autoloader --no-scripts`
   - `npm ci`
   - Kopiowanie aplikacji
   - `npm run build`
   - Kopiowanie `server.php` do root i `public/`

2. **Production stage (`php:8.3-cli`):**
   - Instalacja tylko runtime dependencies (PHP extensions)
   - Kopiowanie aplikacji z build stage
   - Entrypoint: `docker/entrypoint-railway.sh`

**Kluczowe optymalizacje:**
- Kopiowanie `composer.json` i `package.json` PRZED kopiowaniem kodu (lepsze cache'owanie)
- Usunięcie `node_modules` po buildzie (zmniejszenie rozmiaru obrazu)
- Użycie `--no-scripts` w composer, potem ręczne `package:discover`

### Entrypoint (`docker/entrypoint-railway.sh`)

**Funkcje:**
1. Walidacja `APP_KEY` (hard fail jeśli brak)
2. Usunięcie `.env` file (aby używać Railway Variables)
3. Naprawa uprawnień dla `storage` i `bootstrap/cache`
4. Wyczyść wszystkie cache (`config:clear`, `route:clear`, `view:clear`, `cache:clear`)
5. Opcjonalne migracje (tylko jeśli `RUN_MIGRATIONS=true`)
6. `storage:link` (ignoruj jeśli istnieje)
7. Kopiowanie `server.php` jeśli nie istnieje
8. Start `php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"`

**Zasady:**
- ❌ NIE cache'uj konfiguracji (zmienne środowiskowe mogą się zmieniać)
- ❌ NIE uruchamiaj migracji domyślnie (tylko jeśli `RUN_MIGRATIONS=true`)
- ✅ Zawsze wyczyść cache przed startem
- ✅ Zawsze sprawdź `APP_KEY` przed startem

### Railway Configuration

**railway.json:**
```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  },
  "deploy": {
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10,
    "healthcheckPath": "/api/health",
    "healthcheckTimeout": 5000
  }
}
```

### Environment Variables (Railway)

**Wymagane:**
- `APP_KEY` - klucz aplikacji Laravel (wygeneruj: `php artisan key:generate --show`)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-app.up.railway.app`
- `ASSET_URL=https://your-app.up.railway.app` (opcjonalne, ale zalecane)
- `DB_HOST` - host bazy danych Railway
- `DB_PORT` - port bazy danych
- `DB_DATABASE` - nazwa bazy danych
- `DB_USERNAME` - użytkownik bazy danych
- `DB_PASSWORD` - hasło bazy danych
- `PORT` - automatycznie ustawiane przez Railway (nie trzeba ręcznie)

**Opcjonalne:**
- `RUN_MIGRATIONS=true` - jeśli chcesz uruchamiać migracje przy każdym starcie (niezalecane)

### Railway Volume (Persistent Storage)

**Problem:** Storage jest efemeryczne - po każdym deploycie kontener jest odtwarzany i pliki (zdjęcia, uploady) znikają.

**Rozwiązanie:** Użyj Railway Volume dla trwałego storage.

**Konfiguracja w Railway Dashboard:**
1. Przejdź do Railway Dashboard → Twój projekt → Serwis aplikacji
2. Kliknij "Settings" → "Volumes"
3. Dodaj nowy Volume:
   - **Name:** `storage` (lub dowolna nazwa)
   - **Mount Path:** `/data`
4. Zapisz zmiany

**Jak to działa:**
- Entrypoint automatycznie wykrywa volume w `/data`
- Tworzy strukturę katalogów w volume
- Kopiuje istniejące pliki do volume (tylko przy pierwszym uruchomieniu)
- Tworzy symlink `storage/app/public` → `/data/storage/app/public`
- Wszystkie nowe pliki są zapisywane w volume (trwałe)

**Uwaga:** Jeśli volume nie jest zamontowane, aplikacja działa normalnie, ale pliki będą tracone przy każdym deploycie.

---

## Kluczowe lekcje

### 1. Railway wymaga: kontener nie kończy się + port odpowiada HTTP
- Railway NIE wymaga, żeby PID 1 był serwerem HTTP
- Główny proces musi nasłuchiwać na `${PORT}` i odpowiadać HTTP
- `exec` w entrypoint zapewnia, że główny proces jest PID 1

### 2. Entrypoint powinien być minimalny - ZASADA
- Runtime ma serwować HTTP
- Wszystko inne (build, setup, migracje, health) to procesy przed lub obok
- Setup/migracje NIE WOLNO w entrypoint - powinny być w CI/CD, cron, init containers

### 3. PORT jest dynamiczny
- Railway przypisuje losowy port
- Musi być użyty w konfiguracji przed startem
- `php artisan serve` akceptuje `--port` jako argument

### 4. Nie cache'uj konfiguracji w kontenerze
- Railway Variables mogą się zmieniać bez rebuild
- `config:cache` blokuje odczyt zmiennych środowiskowych
- Zawsze używaj `config:clear` przed startem

### 5. APP_KEY jest krytyczny
- Laravel wymaga `APP_KEY` do szyfrowania
- Bez `APP_KEY` aplikacja nie może działać
- Zawsze waliduj `APP_KEY` w entrypoint przed startem

### 6. .env file vs Railway Variables
- `.env` file w kontenerze może zawierać stare wartości z build time
- Railway Variables są źródłem prawdy w produkcji
- Usuń `.env` file w entrypoint, aby używać tylko Railway Variables

### 7. HMR mode vs Production build
- Plik `public/hot` włącza HMR mode w Laravel Vite
- HMR mode generuje URL-e do `localhost:5174` (nie działa w produkcji)
- Zawsze usuń `public/hot` w produkcji

### 8. HTTPS w produkcji
- Railway używa HTTPS, ale Laravel może generować HTTP URL-e
- Wymuś HTTPS przez `URL::forceScheme('https')` w `AppServiceProvider`
- Ustaw `APP_URL` i `ASSET_URL` na HTTPS w Railway Variables

### 9. Multi-stage build optymalizuje cache
- Kopiuj pliki zależności (`composer.json`, `package.json`) PRZED kodem
- Docker cache'uje warstwy, które się nie zmieniają
- Zmniejsza czas builda i rozmiar obrazu

### 10. Healthcheck jest ważny
- Railway używa healthcheck do weryfikacji, że aplikacja działa
- Healthcheck powinien być prosty i szybki
- Wyłącz middleware wymagające APP_KEY dla healthcheck endpoint

---

## Debugging

### Sprawdzanie logów
```bash
railway logs --service delegacje --tail 200
```

### Sprawdzanie zmiennych środowiskowych
```bash
railway run bash -c 'echo $APP_KEY'
railway run bash -c 'echo $APP_URL'
```

### Sprawdzanie czy aplikacja odpowiada
```bash
railway run bash -c 'curl -v http://127.0.0.1:$PORT/'
```

### Sprawdzanie czy Laravel działa
```bash
railway run bash -c 'php artisan --version'
railway run bash -c 'php artisan tinker --execute="echo config(\"app.name\");"'
```

### Sprawdzanie migracji
```bash
railway run bash -c 'php artisan migrate:status'
```

### Uruchamianie migracji
```bash
railway run bash -c 'php artisan migrate --force'
```

### Sprawdzanie czy użytkownik istnieje
```bash
railway run bash -c 'php artisan tinker --execute="echo App\Models\User::where(\"email\", \"test@test.com\")->exists() ? \"EXISTS\" : \"NOT EXISTS\";"'
```

### Tworzenie administratora

**Metoda 1: Przez Railway UI (najprostsze)**
1. Przejdź do Railway Dashboard → Twój projekt → Serwis aplikacji
2. Kliknij "Deployments" → wybierz najnowszy deployment
3. Szukaj "Terminal", "Console" lub "Run Command"
4. Uruchom: `php artisan user:create-admin someone@someone.someone --password=password123`

**Metoda 2: Przez Railway CLI (jeśli dostępne)**
```bash
railway run php artisan user:create-admin someone@someone.someone --password=password123
```

**Metoda 3: Przez tinkera (ręcznie)**
1. Otwórz Railway UI → Terminal/Console
2. Uruchom: `php artisan tinker`
3. Skopiuj i wklej kod z pliku `create_admin.php`:
```php
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$email = 'someone@someone.someone';
$password = 'password123';

$user = User::updateOrCreate(
    ['email' => $email],
    [
        'name' => 'Administrator',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
    ]
);

$adminRole = Role::firstOrCreate(
    ['name' => 'administrator'],
    ['guard_name' => 'web']
);

if (!$user->hasRole('administrator')) {
    $user->assignRole('administrator');
}

echo "✅ Admin user created: {$user->email}\n";
echo "✅ Password: {$password}\n";
```

---


### ✅ Infrastruktura
- ✅ Build przechodzi poprawnie
- ✅ Kontener startuje i nie kończy się
- ✅ `php artisan serve` nasłuchiwa na `${PORT}`
- ✅ Healthcheck działa (`/api/health`)
- ✅ Railway widzi kontener jako "uruchomiony"

### ✅ Aplikacja Laravel
- ✅ Laravel startuje poprawnie
- ✅ `APP_KEY` jest walidowany i używany
- ✅ Railway Variables są używane zamiast `.env` file
- ✅ Cache jest czyszczony przed startem
- ✅ Migracje mogą być uruchamiane (opcjonalnie przez `RUN_MIGRATIONS=true`)

### ✅ Frontend
- ✅ CSS i JS są ładowane przez HTTPS
- ✅ Laravel Vite używa production build (nie HMR)
- ✅ Static files są serwowane poprawnie przez `server.php`
- ✅ Wszystkie URL-e są generowane jako HTTPS

### ✅ Bezpieczeństwo
- ✅ Wszystkie requesty są przez HTTPS
- ✅ Formularze używają HTTPS
- ✅ Brak Mixed Content warnings
- ✅ `APP_KEY` jest wymagany i walidowany

---

## Następne kroki (opcjonalne)

### 1. Automatyczne migracje w CI/CD
Zamiast `RUN_MIGRATIONS=true`, uruchamiaj migracje w GitHub Actions przed deploymentem:
```yaml
- name: Run migrations
  run: railway run bash -c 'php artisan migrate --force'
```

### 2. Monitoring i logowanie
- Dodaj Sentry lub podobne narzędzie do monitorowania błędów
- Skonfiguruj logowanie do zewnętrznego serwisu (np. Logtail)

### 3. Backup bazy danych
- Skonfiguruj automatyczne backup'y bazy danych Railway
- Ustaw harmonogram backup'ów

### 4. Performance optimization
- Rozważ użycie Redis dla cache i sesji
- Skonfiguruj CDN dla static assets (jeśli potrzebne)
- Włącz OPcache w PHP

---

## Historia zmian

- **2026-02-04**: Przejście z Nginx+PHP-FPM na `php artisan serve`
- **2026-02-04**: Naprawienie problemów z APP_KEY i cache
- **2026-02-04**: Naprawienie Mixed Content (usunięcie `public/hot`)
- **2026-02-04**: Wymuszenie HTTPS globalnie (`URL::forceScheme`)
- **2026-02-04**: Naprawienie migracji bazy danych
- **2026-02-04**: Optymalizacja Dockerfile (multi-stage build, cache layers)

---

## Podziękowania

Ten dokument został stworzony na podstawie rzeczywistych problemów napotkanych podczas deploymentu. Wszystkie rozwiązania zostały przetestowane i działają w produkcji.
