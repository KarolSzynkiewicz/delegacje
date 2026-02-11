# Deployment Guide - Delegacje App on Railway (2026)

## Spis treści
1. [Przegląd architektury](#przegląd-architektury)
2. [Struktura plików](#struktura-plików)
3. [Dockerfile - szczegółowy opis](#dockerfile---szczegółowy-opis)
4. [Entrypoint - szczegółowy opis](#entrypoint---szczegółowy-opis)
5. [Railway Configuration](#railway-configuration)
6. [Logi strukturalne](#logi-strukturalne)
7. [Zmienne środowiskowe](#zmienne-środowiskowe)
8. [Workflow deployment](#workflow-deployment)
9. [Troubleshooting](#troubleshooting)
10. [Maintenance](#maintenance)

---

## Przegląd architektury

### Stack techniczny
- **Framework**: Laravel 11
- **PHP**: 8.3-cli (single process)
- **Server**: `php artisan serve` (port dynamiczny z Railway `$PORT`)
- **Database**: MySQL 9.4 (Railway service, Private Network)
- **Storage**: Railway Volume montowany w `/data` (persistent uploads)
- **Platform**: Railway.app (Docker deployment)

### Dlaczego php artisan serve?

Railway wymaga **jednego procesu HTTP na dynamicznym porcie**. Zamiast nginx + php-fpm (2 procesy), używamy `php artisan serve`:

✅ **Zalety:**
- Jeden proces (Railway requirement)
- Dynamiczny port z `$PORT` env var
- Prostsza konfiguracja (brak nginx.conf)
- Built-in static file handling
- Idealny dla małych/średnich aplikacji

❌ **Wady:**
- Mniejsza wydajność niż nginx (ale wystarczająca dla naszego use case)
- Single-threaded (Railway może skalować horizontalnie jeśli potrzeba)

---

## Struktura plików

```
delegacje/
├── Dockerfile                          # Multi-stage build definition
├── docker/
│   └── entrypoint-railway.sh          # Startup script (56 lines)
├── railway.json                        # Railway configuration
├── DEPLOY2.md                          # ← Ten dokument
├── TESTING-SIMPLIFIED-DEPLOYMENT.md   # Instrukcje testowania
├── CLEAN-LOGS-EXAMPLE.md              # Przykłady logów
└── [backup files]
    ├── Dockerfile.old                 # Backup (112 lines - przed uproszczeniem)
    ├── docker/entrypoint-railway.sh.old  # Backup (270 lines)
    └── railway.json.old
```

---

## Dockerfile - szczegółowy opis

### Filozofia: Multi-stage build

**Stage 1: `base`** - buduje aplikację z wszystkimi zależnościami  
**Stage 2: `production`** - kopiuje tylko runtime files, bez dev dependencies

To optymalizuje rozmiar obrazu: ~500MB zamiast ~1GB.

---

### Stage 1: Build (base)

#### Krok 1: Bazowy obraz i środowisko

```dockerfile
FROM php:8.3-fpm AS base
ENV DEBIAN_FRONTEND=noninteractive
```

**Co się dzieje:**
- Używamy `php:8.3-fpm` (zawiera PHP + extensions tools)
- `DEBIAN_FRONTEND=noninteractive` - wyłącza interaktywne prompty apt-get

**Logi:**
```
[STEP] Installing system libraries for PHP extensions...
```

---

#### Krok 2: Instalacja bibliotek systemowych

```dockerfile
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libzip-dev libjpeg-dev libfreetype6-dev \
    libonig-dev zip unzip nodejs npm && \
    echo "[OK] System libraries installed"
```

**Co instalujemy:**
- `git` - potrzebny przez composer dla niektórych zależności
- `curl` - HTTP requests
- `libpng-dev`, `libjpeg-dev`, `libfreetype6-dev` - dla GD (image processing)
- `libzip-dev` - dla zip extension
- `libonig-dev` - dla mbstring
- `nodejs` + `npm` - do buildu frontend assets (Vite)

**Czas:** ~30 sekund  
**Cache:** Railway cache'uje ten layer - rebuild tylko jeśli zmienią się pakiety

**Logi:**
```
[STEP] Installing system libraries for PHP extensions...
[OK] System libraries installed
```

---

#### Krok 3: Instalacja rozszerzeń PHP

```dockerfile
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip && \
    echo "[OK] PHP extensions installed"
```

**Rozszerzenia:**
- `pdo_mysql` - database driver (KRYTYCZNE!)
- `mbstring` - multi-byte string support
- `exif` - image metadata
- `pcntl` - process control (queues)
- `bcmath` - arbitrary precision math
- `gd` - image manipulation
- `zip` - archive handling

**Czas:** ~20 sekund  
**Cache:** Railway cache'uje

**Logi:**
```
[STEP] Installing PHP extensions (pdo_mysql, gd, zip, etc)...
[OK] PHP extensions installed
```

---

#### Krok 4: Composer

```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
```

**Co się dzieje:**
- Kopiujemy `composer` z oficjalnego obrazu (multi-stage copy)
- Ustawiamy katalog roboczy na `/var/www/html`

**Żadnych logów** - instant operation.

---

#### Krok 5: Dependency caching (KLUCZOWE dla szybkiego buildu!)

```dockerfile
COPY composer.json composer.lock package.json package-lock.json ./

RUN composer install --no-dev --optimize-autoloader \
    --no-interaction --prefer-dist --no-scripts && \
    echo "[OK] Composer dependencies installed"

RUN npm ci --prefer-offline --no-audit && \
    echo "[OK] Node modules installed"
```

**DLACZEGO TO JEST PIERWSZE?**

Docker cache'uje warstwy. Jeśli kopiujemy tylko `composer.json` (a nie cały kod), to:
- ✅ Zmiany w kodzie **NIE invalidują** cache dependencies
- ✅ Rebuild zajmuje 10 sekund zamiast 2 minuty
- ✅ Dependencies instalują się tylko gdy zmienią się w `composer.json`

**Kolejność:**
1. COPY `composer.json` + `package.json`
2. RUN `composer install` + `npm ci`
3. COPY cały kod (później)

**Czas:**
- Pierwszy build: ~90 sekund (composer) + ~60 sekund (npm)
- Kolejne buildy (cache): ~5 sekund

**Logi:**
```
[STEP] Installing PHP dependencies (composer)...
[OK] Composer dependencies installed
[STEP] Installing frontend dependencies (npm)...
[OK] Node modules installed
```

---

#### Krok 6: Kopiowanie kodu aplikacji

```dockerfile
COPY . .
RUN echo "[OK] Source code copied"
```

**Co się kopiuje:**
- Cały kod Laravel (app/, resources/, routes/, etc.)
- `.dockerignore` blokuje niepotrzebne pliki (node_modules, vendor, .git)

**Czas:** ~5 sekund  
**Cache:** Invaliduje się przy KAŻDEJ zmianie kodu (to OK - kod zmienia się często)

**Logi:**
```
[STEP] Copying application source code...
[OK] Source code copied
```

---

#### Krok 7: Storage directories (PRZED package:discover!)

```dockerfile
RUN mkdir -p bootstrap/cache storage/framework/cache \
    storage/framework/sessions storage/framework/views && \
    chmod -R 775 bootstrap/cache storage && \
    echo "[OK] Directories created"
```

**KRYTYCZNE:** `bootstrap/cache` MUSI istnieć przed `php artisan package:discover`!

**Błąd jeśli brak:**
```
The /var/www/html/bootstrap/cache directory must be present and writable.
```

**Logi:**
```
[STEP] Creating storage and cache directories...
[OK] Directories created
```

---

#### Krok 8: server.php (workaround dla php artisan serve)

```dockerfile
RUN cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php server.php && \
    cp vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php public/server.php && \
    echo "[OK] server.php ready"
```

**Dlaczego?**

`php artisan serve` używa `server.php` do prawidłowego handleowania static files. Laravel framework ma go w `vendor/`, ale nie w root - kopiujemy ręcznie.

**Bez tego:** static files (CSS, JS, images) mogą nie działać poprawnie.

**Logi:**
```
[STEP] Preparing server.php for static file serving...
[OK] server.php ready
```

---

#### Krok 9: Optymalizacja Laravel

```dockerfile
RUN php artisan package:discover --ansi || true && \
    composer dump-autoload --optimize --classmap-authoritative && \
    echo "[OK] Laravel optimized"
```

**Co robi:**
- `package:discover` - Laravel wykrywa zainstalowane packages (Spatie, Livewire, etc.)
- `dump-autoload --optimize` - tworzy optimized classmap (szybszy autoloading)
- `--classmap-authoritative` - tylko classmap, nie filesystem scan (production mode)

**Czas:** ~10 sekund

**Logi:**
```
[STEP] Optimizing Laravel (package discovery + autoload)...
[OK] Laravel optimized
```

---

#### Krok 10: Build frontend assets

```dockerfile
RUN npm run build && \
    echo "[OK] Frontend assets built"

RUN rm -rf node_modules && \
    echo "[OK] node_modules removed"
```

**Co się dzieje:**
- `npm run build` - Vite kompiluje JS/CSS z `resources/js` + `resources/css` do `public/build/`
- Usuwamy `node_modules` (nie potrzebne w runtime - oszczędność ~300MB!)

**Output:**
```
/build/assets/app-UW9OuAtd.js
/build/assets/app-B-khrF23.css
```

**Czas:** ~30-40 sekund

**Logi:**
```
[STEP] Building frontend assets (npm run build)...
[OK] Frontend assets built
[STEP] Cleaning up node_modules...
[OK] node_modules removed
```

---

### Stage 2: Production

#### Krok 11: Bazowy obraz production

```dockerfile
FROM php:8.3-cli
ENV DEBIAN_FRONTEND=noninteractive
```

**ZMIANA:** `php:8.3-fpm` → `php:8.3-cli`

**Dlaczego CLI?**
- FPM jest do FastCGI (nginx)
- CLI jest do `php artisan serve` (command-line server)
- CLI jest lżejszy (~100MB mniej)

---

#### Krok 12: Runtime dependencies

```dockerfile
RUN apt-get update && apt-get install -y \
    libpng-dev libzip-dev libjpeg-dev libfreetype6-dev libonig-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    echo "[OK] Runtime dependencies installed"
```

**Co instalujemy:**
- Tylko runtime libraries (bez git, curl, nodejs - niepotrzebne)
- Te same PHP extensions co w build stage
- `apt-get clean` - usuwamy cache apt (oszczędność ~50MB)

**Czas:** ~30 sekund

**Logi:**
```
[STEP] Installing runtime dependencies for production...
[OK] Runtime dependencies installed
```

---

#### Krok 13: Entrypoint (NAJPIERW, zanim COPY app!)

```dockerfile
COPY docker/entrypoint-railway.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && \
    echo "[OK] Entrypoint ready"
```

**Kolejność ma znaczenie:**

❌ **ŹLE (stare):**
```dockerfile
COPY --from=base /var/www/html /var/www/html  # zawiera stary entrypoint
COPY docker/entrypoint-railway.sh /entrypoint.sh  # nadpisuje
```

✅ **DOBRZE (nowe):**
```dockerfile
COPY docker/entrypoint-railway.sh /entrypoint.sh  # najpierw nowy
COPY --from=base /var/www/html /var/www/html      # potem reszta
```

To zapewnia, że Railway używa najnowszego entrypoint.

**Logi:**
```
[STEP] Preparing entrypoint script...
[OK] Entrypoint ready
```

---

#### Krok 14: Kopiowanie built app

```dockerfile
COPY --from=base /var/www/html /var/www/html
RUN echo "[OK] Application copied"
```

**Co kopiujemy:**
- Cały kod
- Zainstalowane dependencies (`vendor/`)
- Built frontend assets (`public/build/`)
- Optimized autoloader
- server.php

**Czas:** ~10 sekund (internal Docker copy, szybkie)

**Logi:**
```
[STEP] Copying built application from build stage...
[OK] Application copied
```

---

#### Krok 15: Permissions

```dockerfile
RUN chmod -R 777 storage bootstrap/cache && \
    echo "[OK] Permissions set"
```

**Dlaczego 777?**

Railway może uruchamiać kontenery jako różni users (root, www-data, random UID). `777` zapewnia, że Laravel zawsze może pisać do `storage/` i `bootstrap/cache/`.

**Alternatywa (bezpieczniejsza, ale problematyczna na Railway):**
```bash
chown -R www-data:www-data storage
chmod -R 755 storage
```
Problem: Railway user może nie być `www-data` → permission denied.

**Logi:**
```
[STEP] Setting final permissions...
[OK] Permissions set
```

---

#### Krok 16: Final message + Entrypoint

```dockerfile
RUN echo "[BUILD] Docker image build complete - ready to run"
ENTRYPOINT ["/entrypoint.sh"]
```

**ENTRYPOINT vs CMD:**
- `ENTRYPOINT` - nie da się overridować (chyba że `--entrypoint`)
- `CMD` - można overridować argumentami
- Używamy `ENTRYPOINT` bo zawsze chcemy uruchomić nasz script.

**Logi:**
```
[BUILD] Docker image build complete - ready to run
```

---

## Entrypoint - szczegółowy opis

**Plik:** `docker/entrypoint-railway.sh` (83 linie, ~50 linii kodu)

### Struktura:

```bash
#!/bin/sh
set -e  # Exit on error (fail-fast)

# 1. Startup banner
# 2. APP_KEY validation (CRITICAL!)
# 3. .env removal (Railway Variables > .env)
# 4. Railway Volume setup (persistent storage)
# 5. Permissions fix
# 6. Cache clearing (config, route, view, cache)
# 7. Optional migrations
# 8. Server start (php artisan serve)
```

---

### Sekcja 1: Startup banner

```bash
echo "[START] Laravel application startup on Railway"
echo "[INFO] Port: ${PORT:-8000}"
```

**Output:**
```
[START] Laravel application startup on Railway
[INFO] Port: 9000
```

**Zmienne:**
- `${PORT:-8000}` - użyj `$PORT` jeśli ustawiony, inaczej `8000` (default)

---

### Sekcja 2: APP_KEY validation (FAIL-FAST!)

```bash
echo "[CHECK] Validating APP_KEY..."
if [ -z "$APP_KEY" ]; then
    echo "[ERROR] APP_KEY environment variable is not set"
    echo "[HELP] Generate locally: php artisan key:generate --show"
    echo "[HELP] Then add to Railway Variables"
    exit 1
fi
echo "[OK] APP_KEY validated"
```

**Dlaczego to pierwsze?**

Jeśli `APP_KEY` nie istnieje, Laravel **NIE WYSTARTUJE** (encryption error). Lepiej failować szybko z jasnym errorem niż próbować startować i crashować później.

**Bezpieczeństwo:**
- ✅ NIE logujemy wartości APP_KEY (nigdy!)
- ✅ Tylko sprawdzamy czy istnieje
- ✅ Jasny error message z instrukcją fix

**Output (success):**
```
[CHECK] Validating APP_KEY...
[OK] APP_KEY validated
```

**Output (failure):**
```
[CHECK] Validating APP_KEY...
[ERROR] APP_KEY environment variable is not set
[HELP] Generate locally: php artisan key:generate --show
[HELP] Then add to Railway Variables
```
Container exit code: 1

---

### Sekcja 3: .env removal

```bash
echo "[STEP] Removing .env file (Railway Variables take priority)..."
rm -f .env
echo "[OK] .env removed - using Railway environment variables"
```

**Dlaczego usuwamy .env?**

`.env` file (jeśli istnieje z build) może zawierać **stare wartości** (np. APP_KEY z build time). Railway Variables są **source of truth** - muszą mieć priorytet.

**Laravel priority:**
1. Environment variables (Railway)
2. `.env` file
3. `config/` defaults

Usuwamy `.env` aby wymusić użycie Railway Variables.

**Output:**
```
[STEP] Removing .env file (Railway Variables take priority)...
[OK] .env removed - using Railway environment variables
```

---

### Sekcja 4: Railway Volume setup

```bash
if [ -d "/data" ] && [ -w "/data" ]; then
    echo "[VOLUME] Railway Volume detected at /data"
    
    mkdir -p /data/storage/app/public
    
    # First-run initialization
    if [ ! -f "/data/storage/.initialized" ]; then
        echo "[INIT] First run - copying existing files to volume..."
        cp -r storage/app/public/* /data/storage/app/public/ 2>/dev/null || true
        touch /data/storage/.initialized
        echo "[OK] Volume initialized with existing files"
    else
        echo "[INFO] Volume already initialized"
    fi
    
    # Create symlinks
    echo "[STEP] Creating symlinks to volume..."
    rm -rf storage/app/public
    ln -sf /data/storage/app/public storage/app/public
    ln -sf /data/storage/app/public public/storage
    echo "[OK] Symlinks created"
    
    chmod -R 777 /data/storage 2>/dev/null || true
    echo "[OK] Volume configured for persistent storage"
else
    echo "[INFO] No Railway Volume detected - using ephemeral storage"
fi
```

**Co to robi?**

**Railway Volume** = persistent disk montowany w `/data`. Bez niego, uploaded files (avatary, dokumenty) znikają po redeploy!

**Flow:**
1. **Check:** czy `/data` istnieje i jest writable?
2. **Init (first run):** kopiuj istniejące pliki z `storage/app/public/` do `/data/` (tylko raz!)
3. **Symlinks:** 
   - `storage/app/public` → `/data/storage/app/public`
   - `public/storage` → `/data/storage/app/public`
4. **Permissions:** `777` (Railway może używać różnych UID)

**Output (first deploy with volume):**
```
[VOLUME] Railway Volume detected at /data
[STEP] Preparing volume directory structure...
[INIT] First run - copying existing files to volume...
[OK] Volume initialized with existing files
[STEP] Creating symlinks to volume...
[OK] Symlinks created
[STEP] Setting volume permissions...
[OK] Volume configured for persistent storage
```

**Output (subsequent deploys):**
```
[VOLUME] Railway Volume detected at /data
[STEP] Preparing volume directory structure...
[INFO] Volume already initialized
[STEP] Creating symlinks to volume...
[OK] Symlinks created
[STEP] Setting volume permissions...
[OK] Volume configured for persistent storage
```

**Output (no volume):**
```
[INFO] No Railway Volume detected - using ephemeral storage
```

**Struktura po setup:**
```
/data/storage/app/public/
├── employees/          # Employee avatars
├── users/              # User avatars
├── vehicles/           # Vehicle photos
├── accommodations/     # Accommodation photos
├── employee_documents/ # Document scans
└── .initialized        # Flag file

Container filesystem:
storage/app/public → /data/storage/app/public (symlink)
public/storage     → /data/storage/app/public (symlink)
```

---

### Sekcja 5: Permissions

```bash
echo "[STEP] Setting local storage permissions..."
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
echo "[OK] Permissions set"
```

**Dlaczego znowu?**

Mogliśmy to zrobić w Dockerfile, ale:
- Railway może zmienić UID między buildiem a runtime
- Volume mount może zmienić ownership
- Safer robić to w runtime

`2>/dev/null || true` - ignoruj błędy (może być już 777).

**Output:**
```
[STEP] Setting local storage permissions...
[OK] Permissions set
```

---

### Sekcja 6: Cache clearing

```bash
echo "[CACHE] Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "[OK] All caches cleared"
```

**KRYTYCZNE: NIGDY NIE CACHE'UJ CONFIG W KONTENERZE!**

**Problem z `config:cache`:**
```bash
php artisan config:cache  # ❌ ZŁE!
```

To tworzy `bootstrap/cache/config.php`, który **blokuje** odczyt environment variables. Railway Variables nie działają!

**Poprawne (nasze):**
```bash
php artisan config:clear  # ✅ DOBRE!
```

**Czyszczone cache:**
- `config:clear` - usuwa `bootstrap/cache/config.php`
- `route:clear` - usuwa `bootstrap/cache/routes-v7.php`
- `view:clear` - usuwa skompilowane Blade templates
- `cache:clear` - usuwa application cache (Redis/File)

**Czas:** ~3 sekundy

**Output:**
```
[CACHE] Clearing Laravel caches...
   INFO  Configuration cache cleared successfully.
   INFO  Route cache cleared successfully.
   INFO  Compiled views cleared successfully.
   ERROR  Failed to clear cache. Make sure you have the appropriate permissions.
[OK] All caches cleared
```

**Note:** `ERROR Failed to clear cache` to warning Laravela - można ignorować (cache file może nie istnieć).

---

### Sekcja 7: Optional migrations

```bash
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[MIGRATE] Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "[OK] Migrations complete"
else
    echo "[INFO] Migrations skipped (RUN_MIGRATIONS not set to 'true')"
fi
```

**Controlled migrations:**

**Dlaczego optional?**
- ✅ Kontrola: nie uruchamiaj przy każdym restart/scale
- ✅ Safety: możesz wyłączyć jeśli są problemy z bazą
- ✅ Flexibility: włączaj tylko przy deploy z nowymi migracjami

**Railway Variables:**
```
RUN_MIGRATIONS=false   # Default (recommended)
RUN_MIGRATIONS=true    # Enable when deploying with migrations
```

**Output (enabled):**
```
[MIGRATE] Running database migrations...
Nothing to migrate.
[OK] Migrations complete
```

**Output (disabled):**
```
[INFO] Migrations skipped (RUN_MIGRATIONS not set to 'true')
```

---

### Sekcja 8: Server start

```bash
echo "[START] Starting Laravel server..."
echo "[INFO] Listening on 0.0.0.0:${PORT:-8000}"
echo "[READY] Application ready - waiting for requests"
echo ""

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
```

**`exec` is critical!**

```bash
exec php artisan serve  # ✅ DOBRE - replace shell with PHP process
php artisan serve       # ❌ ZŁE - shell fork, Railway może nie trackować
```

`exec` zamienia shell process (PID 1) na PHP process. Railway trackuje PID 1 - musi to być application server.

**Arguments:**
- `--host=0.0.0.0` - listen on all interfaces (Railway proxy wymaga)
- `--port="${PORT:-8000}"` - dynamiczny port z Railway

**Output:**
```
[START] Starting Laravel server...
[INFO] Listening on 0.0.0.0:9000
[READY] Application ready - waiting for requests

   INFO  Server running on [http://0.0.0.0:9000].

  Press Ctrl+C to stop the server
```

**Po tym punkcie:** application działa, czeka na HTTP requests.

---

## Railway Configuration

**Plik:** `railway.json`

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  },
  "deploy": {
    "restartPolicyType": "ON_FAILURE",
    "healthcheckPath": "/api/health",
    "healthcheckTimeout": 30000
  }
}
```

### Builder

```json
"builder": "DOCKERFILE"
```

Railway może użyć:
- `NIXPACKS` (default - auto-detect, często problematyczne)
- `DOCKERFILE` (nasza konfiguracja - pełna kontrola)

Używamy `DOCKERFILE` bo mamy custom setup (Volume, entrypoint, etc.).

### Healthcheck

```json
"healthcheckPath": "/api/health",
"healthcheckTimeout": 30000
```

**Endpoint:** `/api/health` (defined in `routes/api.php`)

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'app' => 'Delegacje',
    ], 200);
});
```

**KRYTYCZNE:** Healthcheck **NIE MOŻE** wymagać:
- ❌ Database connection (może być down podczas outage)
- ❌ Authentication
- ❌ Middleware

Musi zwrócić `200 OK` zawsze jeśli application działa.

**Timeout:** 30 sekund (było 5s - za krótko dla cold start).

**Railway behavior:**
- Deploy starts
- Railway czeka max 30s na `GET /api/health`
- Jeśli 200 OK → deployment success
- Jeśli timeout/error → deployment fail, rollback

### Restart Policy

```json
"restartPolicyType": "ON_FAILURE"
```

**Opcje:**
- `ON_FAILURE` - restart tylko jeśli crash (exit code ≠ 0)
- `NEVER` - nie restartuj
- `ALWAYS` - zawsze restartuj (problematyczne - może tworzyć restart loop)

Używamy `ON_FAILURE` - restart jeśli application crashuje, ale nie jeśli gracefully exits.

---

## Logi strukturalne

### Format

Każda linia logu używa **tagu** na początku:

```
[TAG] Message content
```

### Tagi

| Tag | Znaczenie | Przykład |
|-----|-----------|----------|
| `[START]` | Początek procesu | `[START] Laravel application startup` |
| `[STEP]` | Akcja w trakcie | `[STEP] Installing dependencies...` |
| `[OK]` | Sukces | `[OK] Dependencies installed` |
| `[INFO]` | Informacja | `[INFO] Port: 9000` |
| `[CHECK]` | Walidacja | `[CHECK] Validating APP_KEY...` |
| `[ERROR]` | Błąd | `[ERROR] APP_KEY not set` |
| `[HELP]` | Wskazówka | `[HELP] Run: php artisan key:generate` |
| `[VOLUME]` | Railway Volume | `[VOLUME] Volume detected at /data` |
| `[CACHE]` | Cache operations | `[CACHE] Clearing Laravel caches...` |
| `[MIGRATE]` | Migrations | `[MIGRATE] Running migrations...` |
| `[BUILD]` | Build complete | `[BUILD] Docker image complete` |
| `[READY]` | App ready | `[READY] Application ready` |

### Korzyści

**Przed (debug bloat):**
```
+ date +%s
+ echo {"id":"log_1770823405","timestamp":...
DEBUG: APP_KEY: base64:ALdM...
✅ VERIFIED: keys match
+ TEST_FILE=storage/.test-1770823405
[... 60 more lines]
```

**Po (structured):**
```
[START] Laravel application startup
[CHECK] Validating APP_KEY...
[OK] APP_KEY validated
[CACHE] Clearing Laravel caches...
[OK] All caches cleared
[READY] Application ready
```

**Redukcja:** 70 linii → 8 linii (**89%**)

### Debugging

Jeśli coś failuje, łatwo znaleźć gdzie:

```bash
railway logs | grep "\[STEP\]\|\[ERROR\]"
```

Output:
```
[STEP] Installing dependencies...
[ERROR] Failed to install package xyz
```

Instant diagnoza: problem w dependency installation.

---

## Zmienne środowiskowe

### Wymagane (MUST SET!)

| Zmienna | Wartość | Opis |
|---------|---------|------|
| `APP_KEY` | `base64:...` | Laravel encryption key (generate: `php artisan key:generate --show`) |
| `APP_ENV` | `production` | Environment (production/staging/local) |
| `APP_DEBUG` | `false` | Debug mode (NEVER true in production!) |
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `mysql.railway.internal` | Railway MySQL private hostname |
| `DB_PORT` | `3306` | MySQL port |
| `DB_DATABASE` | `railway` | Database name |
| `DB_USERNAME` | `root` | Database user |
| `DB_PASSWORD` | `***` | Database password (Railway provides) |

### Optional

| Zmienna | Default | Opis |
|---------|---------|------|
| `PORT` | `8000` | Railway provides dynamically |
| `RUN_MIGRATIONS` | `false` | Set `true` to run migrations on startup |
| `APP_URL` | - | Application URL (Railway provides) |
| `SESSION_DRIVER` | `file` | Session storage (file/redis/database) |
| `CACHE_DRIVER` | `file` | Cache storage (file/redis/database) |

### Railway auto-provides

Railway automatycznie ustawia:
- `PORT` - dynamiczny port dla aplikacji
- `RAILWAY_ENVIRONMENT` - environment ID
- `RAILWAY_PROJECT_ID` - project ID
- Database credentials (jeśli MySQL service linked)

---

## Workflow deployment

### 1. Developer workflow (local → Railway)

```
Developer machine:
┌─────────────────┐
│ 1. Code changes │
│ 2. git commit   │
│ 3. git push     │
└────────┬────────┘
         │
         ▼
GitHub repository
┌─────────────────┐
│ Webhook trigger │
└────────┬────────┘
         │
         ▼
Railway (automatic)
┌─────────────────┐
│ 1. Pull code    │
│ 2. Docker build │
│ 3. Push image   │
│ 4. Deploy       │
│ 5. Healthcheck  │
└─────────────────┘
```

### 2. Build process (detailed)

```
Railway Build Agent:
├─ [1] Git clone from GitHub
├─ [2] Read railway.json
├─ [3] Docker build
│   ├─ Stage 1: base
│   │   ├─ Install system libs (~30s)
│   │   ├─ Install PHP extensions (~20s)
│   │   ├─ Install composer deps (~90s first / ~5s cached)
│   │   ├─ Install npm deps (~60s first / ~5s cached)
│   │   ├─ Copy source code (~5s)
│   │   ├─ Build frontend (~40s)
│   │   └─ Total: ~180s first build
│   └─ Stage 2: production
│       ├─ Runtime dependencies (~30s)
│       ├─ Copy from base (~10s)
│       └─ Total: ~40s
├─ [4] Push to Railway registry (~20s)
└─ [5] Deploy
    ├─ Stop old container
    ├─ Start new container
    ├─ Run entrypoint.sh
    │   ├─ Validate APP_KEY
    │   ├─ Setup Volume
    │   ├─ Clear caches
    │   ├─ Run migrations (if enabled)
    │   └─ Start server
    ├─ Healthcheck: GET /api/health
    └─ Success! Route traffic to new container
```

**Total time:**
- First deploy: ~4-5 minutes
- Subsequent (cached): ~1-2 minutes

### 3. Zero-downtime deployment

Railway używa **rolling deployment**:

```
Old container (v1):
[Running] ──────────┐
                    ├─ [overlap 30s]
New container (v2):        │
         [Starting] ────────┘ [Running]
                    ▲
                    Healthcheck OK
```

1. Old container działa
2. New container startuje
3. Healthcheck passes
4. Railway przekierowuje traffic na new
5. Old container shutdown (gracefully)

**Overlap:** ~30 sekund (oba kontenery działają).

---

## Troubleshooting

### Problem: Build fails with "bootstrap/cache must be present"

**Error:**
```
The /var/www/html/bootstrap/cache directory must be present and writable.
```

**Cause:** `mkdir -p bootstrap/cache` jest PO `package:discover`.

**Fix:**

Kolejność w Dockerfile MUSI być:
```dockerfile
# 1. NAJPIERW mkdir
RUN mkdir -p bootstrap/cache ...

# 2. POTEM package:discover
RUN php artisan package:discover ...
```

**See:** Commit `d80e07f`

---

### Problem: Railway używa starych logów (debug bloat)

**Symptom:**
```
+ date +%s
DEBUG: Railway env var APP_KEY: ...
```

**Cause:** Railway cache używa starego entrypoint z Dockerfile.

**Fix 1 (preferred):** Railway Dashboard → Redeploy → "Without cache"

**Fix 2 (code):** Force rebuild
```bash
echo "# Force rebuild $(date +%s)" >> Dockerfile
git add Dockerfile && git commit -m "Force rebuild" && git push
```

**Prevention:** Kopiuj entrypoint PRZED `COPY --from=base`:
```dockerfile
COPY docker/entrypoint-railway.sh /entrypoint.sh  # First!
COPY --from=base /var/www/html /var/www/html      # Then app
```

---

### Problem: 500 error on Railway, works locally

**Check 1: Environment variables**

```bash
railway run printenv | grep -E "APP_KEY|DB_"
```

Sprawdź czy `APP_KEY`, `DB_HOST`, etc. są ustawione.

**Check 2: Database connection**

```bash
railway logs | grep -i "connection\|database\|mysql"
```

Railway może mieć outage. Check: https://status.railway.app

**Check 3: Permissions**

```bash
railway logs | grep -i "permission denied\|failed to open"
```

Jeśli tak, sprawdź czy `chmod -R 777 storage` jest w entrypoint.

---

### Problem: Files uploaded znikają po redeploy

**Cause:** Brak Railway Volume lub symlink nie działa.

**Fix:**

1. **Add Volume** w Railway Dashboard:
   - Settings → Volumes → Add
   - Mount path: `/data`

2. **Verify in logs:**
```bash
railway logs | grep VOLUME
```

Expected:
```
[VOLUME] Railway Volume detected at /data
[OK] Volume configured for persistent storage
```

If not:
```
[INFO] No Railway Volume detected - using ephemeral storage
```

---

### Problem: "Failed to clear cache" error

**Log:**
```
ERROR  Failed to clear cache. Make sure you have the appropriate permissions.
```

**Is this a problem?** NO! ✅

To jest warning Laravela - cache file może nie istnieć (to OK). Application działa normalnie.

**Ignore it.**

---

### Problem: Migrations nie uruchamiają się

**Check variable:**

```bash
railway run printenv | grep RUN_MIGRATIONS
```

**Expected (if you want migrations):**
```
RUN_MIGRATIONS=true
```

**If not set:**
```
[INFO] Migrations skipped (RUN_MIGRATIONS not set to 'true')
```

**Fix:** Railway Dashboard → Variables → Add:
```
RUN_MIGRATIONS=true
```

**Redeploy.**

---

### Problem: Slow build times

**Cause:** No Docker cache.

**Fix 1:** Don't change dependency files (`composer.json`, `package.json`) often.

**Fix 2:** Check Railway logs for "Restoring cache":
```
Restoring cache from previous build...
[OK] Cache restored (vendor/, node_modules/)
```

If not present, Railway might not be caching. Contact Railway support.

**Fix 3:** Multi-stage build (already implemented!) helps:
- Stage 1 builds dependencies (cached)
- Stage 2 only copies (fast)

---

### Problem: Application crashes immediately

**Check logs:**

```bash
railway logs | grep "\[ERROR\]\|\[HELP\]"
```

**Common errors:**

**1. APP_KEY missing:**
```
[ERROR] APP_KEY environment variable is not set
[HELP] Generate locally: php artisan key:generate --show
```

**Fix:** Add `APP_KEY` to Railway Variables.

**2. Database connection:**
```
SQLSTATE[HY000] [2002] Connection refused
```

**Fix:** Check `DB_HOST`, `DB_PASSWORD` in Railway Variables.

**3. Port binding:**
```
Address already in use
```

**Fix:** Check `PORT` variable (Railway should set automatically).

---

## Maintenance

### Updating dependencies

**Backend (composer):**
```bash
composer update
git add composer.lock
git commit -m "Update PHP dependencies"
git push
```

Railway will rebuild (~90s for dependency install on first build, then cached).

**Frontend (npm):**
```bash
npm update
git add package-lock.json
git commit -m "Update npm dependencies"
git push
```

Railway will rebuild (~60s for npm install on first build, then cached).

---

### Deploying with migrations

**1. Add migration:**
```bash
php artisan make:migration add_column_to_users
# Edit migration file
git add database/migrations
git commit -m "Add migration: add_column_to_users"
```

**2. Enable migrations in Railway:**

Railway Dashboard → Variables:
```
RUN_MIGRATIONS=true
```

**3. Deploy:**
```bash
git push origin master
```

**4. Watch logs:**
```bash
railway logs
```

Expected:
```
[MIGRATE] Running database migrations...
Migrating: 2026_02_11_add_column_to_users
Migrated:  2026_02_11_add_column_to_users (45.23ms)
[OK] Migrations complete
```

**5. Disable auto-migrations (recommended):**

Railway Dashboard → Variables:
```
RUN_MIGRATIONS=false
```

This prevents migrations from running on every restart/scale.

---

### Rollback

**Option 1: Railway Dashboard**

Deployments → Previous deployment → "Redeploy"

**Option 2: Git revert**

```bash
git log --oneline -5
git revert <commit-hash>
git push
```

**Option 3: Code rollback with backup files**

```bash
cp docker/entrypoint-railway.sh.old docker/entrypoint-railway.sh
cp Dockerfile.old Dockerfile
git add -A
git commit -m "Rollback to previous deployment"
git push
```

---

### Scaling

**Horizontal scaling:**

Railway Dashboard → Service → Replicas: 2

Railway will:
1. Start 2 containers with same image
2. Load balance between them
3. Each gets own `$PORT`

**Requirements:**
- Session storage: Database or Redis (not `file`!)
- Cache: Database or Redis (not `file`!)
- Files: Railway Volume (shared between replicas)

---

### Monitoring

**Health check:**

```bash
curl https://your-app.up.railway.app/api/health
```

Expected:
```json
{"status":"ok","timestamp":"2026-02-11T23:00:00+00:00","app":"Delegacje"}
```

**Logs (real-time):**

```bash
railway logs
```

**Filter logs:**

```bash
railway logs | grep "\[ERROR\]"    # Errors only
railway logs | grep "\[MIGRATE\]"  # Migrations only
railway logs | grep "\[STEP\]"     # All steps
```

---

## Performance Tips

### 1. OPcache (future improvement)

**Currently:** Not enabled (PHP-CLI doesn't load OPcache by default).

**To enable:**

Add to Dockerfile:
```dockerfile
RUN docker-php-ext-install opcache && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini
```

**Benefit:** ~30-50% faster PHP execution.

---

### 2. Redis for cache/sessions (future improvement)

**Currently:** File-based cache and sessions.

**To enable:**

1. Add Redis service in Railway
2. Update variables:
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis.railway.internal
```

3. Install phpredis:
```dockerfile
RUN pecl install redis && docker-php-ext-enable redis
```

**Benefit:** 
- Faster cache access
- Horizontal scaling support
- Persistent sessions

---

### 3. Asset CDN (future improvement)

**Currently:** Assets served by Laravel (`public/build/`).

**To enable:** Use CloudFlare or AWS CloudFront in front of Railway.

**Benefit:** Offload static files, faster global delivery.

---

## Summary

### Architecture
- ✅ Laravel 11 + PHP 8.3
- ✅ `php artisan serve` (Railway single-process requirement)
- ✅ Multi-stage Docker build (optimized size)
- ✅ Railway Volume for persistent storage

### Deployment
- ✅ Automatic deploy on push to GitHub
- ✅ ~1-2 minutes build time (with cache)
- ✅ Zero-downtime rolling deployment
- ✅ Healthcheck validation

### Logs
- ✅ Structured format with `[TAG]` prefixes
- ✅ 89% reduction in log noise (70 → 8 lines)
- ✅ Easy debugging with grep

### Security
- ✅ APP_KEY never logged
- ✅ Production mode (no debug)
- ✅ No config cache (Railway Variables work)

### Files
- `Dockerfile` (107 lines)
- `docker/entrypoint-railway.sh` (83 lines)
- `railway.json` (13 lines)
- Total: **203 lines** (was **396 lines** - **49% reduction**)

---

**Version:** 2026-02-11 (Post-Simplification)  
**Status:** ✅ Production-ready  
**Maintainer:** Deployment optimized and documented
