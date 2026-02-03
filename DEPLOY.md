# Deployment Guide - Railway.app

## Historia problemów i rozwiązań

### Początkowy stan
- Aplikacja Laravel z Dockerfile
- Deployment na Railway.app
- Problem: "Application failed to respond" / 502 errors

### Główne problemy i próby naprawy

#### Problem 1: Composer install z package:discover
**Symptom:** `Script @php artisan package:discover --ansi handling the post-autoload-dump event returned with error code 1`

**Próby naprawy:**
- Użycie `--no-scripts` w `composer install`
- Ręczne uruchomienie `php artisan package:discover` po kopiowaniu plików
- Tworzenie minimalnego `.env` przed `composer install`
- Tworzenie katalogów cache przed `composer install`

**Rozwiązanie:** Kopiowanie wszystkich plików aplikacji PRZED `composer install --no-scripts`, potem ręczne uruchomienie `package:discover`.

#### Problem 2: PDO MySQL driver missing
**Symptom:** `could not find driver (Connection: mysql)`

**Rozwiązanie:** Dodanie `docker-php-ext-enable pdo_mysql` w Dockerfile production stage.

#### Problem 3: Supervisor vs Nginx jako główny proces
**Początkowy setup:**
- Supervisor zarządzał Nginx + PHP-FPM
- Supervisor był PID 1

**Problem:** Railway wymaga, żeby kontener nie zakończył się i żeby na zadeklarowanym porcie pojawiła się odpowiedź HTTP.

**WAŻNE:** Railway NIE wymaga, żeby PID 1 był serwerem HTTP. Supervisor może być PID 1 i działać poprawnie, jeśli:
- Nie forkuje w sposób kończący PID 1
- Nie uruchamia procesów w tle bez exec
- Nie kończy się po starcie childrenów

**Problem był w:** sposobie użycia Supervisora, braku kontroli nad lifecycle PID 1.

**Próby naprawy:**
- Przełączenie z Unix socket na TCP (127.0.0.1:9000) dla PHP-FPM
- Zmiana uprawnień (nginx:nginx vs www-data:www-data)
- Przełączenie z Alpine na Ubuntu (prostsze uprawnienia)

**Rozwiązanie:** Usunięcie Supervisora, Nginx jako PID 1, PHP-FPM w tle. To była rozsądna decyzja, ale nie jedyna możliwa.

#### Problem 4: Dynamic PORT handling
**Symptom:** `invalid port in "${PORT:-80}"` - Nginx nie interpretował zmiennej środowiskowej

**Próby naprawy:**
- `sed` do podmiany PORT
- `envsubst` (wymaga `gettext-base`)
- Różne podejścia do escape'owania znaków specjalnych

**Rozwiązanie:** Użycie `envsubst '${PORT}'` w entrypoint przed startem Nginx.

#### Problem 5: Przeinżynierowany entrypoint
**Symptom:** 
- Entrypoint robił za dużo (migracje, cache, setup)
- Używał `ps` (nie było `procps` w obrazie)
- Uruchamiał Nginx wielokrotnie
- PID 1 kończył się → Railway zabijał kontener

**Błędy w entrypoint:**
- Migracje w entrypoint (NIE WOLNO - powinny być osobno)
- `storage:link` przy każdym starcie (NIE WOLNO - powinno być w build/CI)
- Cache clear/cache przy każdym starcie (NIE WOLNO - powinno być w build/CI)
- Sprawdzanie bazy danych w pętli (NIE WOLNO - to nie jest odpowiedzialność runtime)
- Użycie `ps` bez zainstalowanego pakietu (NIE WOLNO - użyj `/proc/1/status`, `ss`, `pgrep`)
- Uruchamianie Nginx w tle (`&`) zamiast `exec` (NIE WOLNO - PID 1 musi być głównym procesem)

**Główny problem:** Zbyt wiele odpowiedzialności wrzucone do runtime'u. Runtime ma serwować HTTP. Wszystko inne to procesy przed lub obok.

**Rozwiązanie:** Minimalny entrypoint:
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

#### Problem 6: Migracja bazy danych
**Symptom:** `SQLSTATE[HY000]: General error: 1830 Column 'payroll_id' cannot be NOT NULL`

**Problem:** Migracja próbuje ustawić `payroll_id` jako NOT NULL, ale istnieją foreign key constraints.

**BŁĄD KRYTYCZNY:** Ignorowanie błędu migracji = brak gwarancji spójności aplikacji.

**ZAKAZ:** Kontener produkcyjny NIE POWINIEN się uruchomić, jeśli migracje nie przeszły. Inaczej aplikacja działa na niespójnym schemacie bazy danych.

**Status:** BLOKADA PRODUCTION - migracja musi być naprawiona przed deploymentem.

#### Problem 7: envsubst missing (gettext-base)
**Symptom:** `/usr/local/bin/entrypoint.sh: 7: envsubst: not found`

**Problem:** Entrypoint używał `envsubst` do podmiany PORT, ale pakiet `gettext-base` nie był zainstalowany w obrazie.

**Rozwiązanie:** Dodanie `gettext-base` do Dockerfile w sekcji runtime dependencies.

#### Problem 8: Nginx nie loguje po starcie
**Symptom:** 
- W logach widoczne: "Starting Nginx..."
- Brak dalszych logów z Nginx
- Aplikacja zwraca 502 Bad Gateway

**Problem:** Nginx startuje, ale:
- Może nie logować (jeśli nie ma requestów)
- Może się crashować zaraz po starcie
- Może nie nasłuchiwać na właściwym porcie

**Próby naprawy:**
- Dodanie `2>&1` do exec nginx (żeby widzieć błędy)
- Usunięcie CMD z Dockerfile (entrypoint sam wszystko obsługuje)
- Dodanie testu konfiguracji Nginx przed startem
- Dodanie logowania portu przed startem

**Status:** W trakcie debugowania - Nginx startuje (konfiguracja OK), ale brak potwierdzenia, że nasłuchuje i odpowiada.

### Finalna architektura

#### Dockerfile
- Multi-stage build (build + production)
- Ubuntu-based (php:8.3-fpm)
- Nginx + PHP-FPM w jednym kontenerze
- Nginx jako główny proces (CMD)

#### Entrypoint
- Minimalny - tylko podmiana PORT i start procesów
- Brak setupu, migracji, cache
- PHP-FPM w tle, Nginx jako PID 1

#### Nginx config
- Nasłuchuje na `0.0.0.0:${PORT}` (podmieniane przez envsubst)
- Reverse proxy do PHP-FPM na 127.0.0.1:9000
- Logi do stdout/stderr

#### PHP-FPM config
- Nasłuchuje na 127.0.0.1:9000 (TCP)
- Użytkownik www-data
- Dynamic process management

### Kluczowe lekcje

1. **Railway wymaga: kontener nie kończy się + port odpowiada HTTP**
   - Railway NIE wymaga, żeby PID 1 był serwerem HTTP
   - Supervisor może być PID 1, jeśli nie kończy się po starcie childrenów
   - Główny proces musi nasłuchiwać na `${PORT}` i odpowiadać HTTP

2. **Entrypoint powinien być minimalny - ZASADA**
   - Runtime ma serwować HTTP
   - Wszystko inne (build, setup, migracje, health) to procesy przed lub obok
   - Setup/migracje NIE WOLNO w entrypoint - powinny być w CI/CD, cron, init containers

3. **PORT jest dynamiczny**
   - Railway przypisuje losowy port
   - Musi być podmieniony w konfiguracji przed startem
   - `envsubst` modyfikuje plik runtime'owo (kontener nie jest 100% immutable - OK w Railway)

4. **Static test first - ZASADA DIAGNOSTYCZNA**
   - Najpierw udowodnij, że port jest otwarty (static 200 OK)
   - Oddziel "czy port żyje" od "czy Laravel działa"
   - Potem dopiero dodawaj PHP/Laravel

5. **Nie używaj narzędzi, których nie ma**
   - `ps` wymaga `procps` - NIE WOLNO używać bez instalacji
   - Alternatywy: `/proc/1/status`, `ss`, `pgrep`
   - W kontenerach minimalnych może nie być standardowych narzędzi

### Obecny stan

**Działa:**
- ✅ Build przechodzi
- ✅ Nginx startuje jako PID 1
- ✅ PORT jest podmieniany
- ✅ PHP-FPM startuje w tle

**BLOKADY PRODUCTION:**
- ❌ Migracja bazy danych (payroll_id NOT NULL) - KONIECZNE przed deploymentem
- ❌ Setup aplikacji (storage:link, cache) - NIE WOLNO w entrypoint, musi być w CI/CD lub init script
- ⚠️ Przywrócenie pełnej konfiguracji Laravel (obecnie static 200 OK dla testu)

### Następne kroki

1. **Jeśli static 200 OK działa:**
   - Przywrócić pełną konfigurację Nginx dla Laravel
   - Dodać setup aplikacji do CI/CD (nie entrypoint)

2. **Naprawić migrację:**
   - Albo `payroll_id` nullable
   - Albo usunąć `ON DELETE SET NULL` z foreign key

3. **Przenieść setup do CI/CD:**
   - `php artisan storage:link`
   - `php artisan config:cache`
   - `php artisan route:cache`
   - `php artisan view:cache`

4. **Migracje:**
   - Uruchamiać przez Railway CLI: `railway run php artisan migrate --force`
   - Lub przez init container
   - Lub przez CI/CD przed deploymentem

### Pliki konfiguracyjne

#### docker/entrypoint.sh
```bash
#!/bin/sh
# Minimal entrypoint - only start PHP-FPM and Nginx
# Railway needs PID 1 to be Nginx

# Substitute PORT in nginx config
export PORT=${PORT:-80}
envsubst '${PORT}' < /etc/nginx/sites-available/default > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/sites-available/default

# Start PHP-FPM in background
php-fpm -D

# Execute Nginx as main process (PID 1)
exec nginx -g 'daemon off;'
```

#### docker/nginx.conf
```nginx
server {
    listen 0.0.0.0:${PORT};
    server_name _;
    root /var/www/html/public;
    index index.php;

    access_log /dev/stdout;
    error_log /dev/stderr;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }

    location ~ /\. {
        deny all;
    }
}
```

#### Dockerfile (kluczowe części)
```dockerfile
# Stage 2: Production
FROM php:8.3-fpm

# Runtime dependencies (nginx, bez supervisor)
RUN apt-get update && apt-get install -y \
    nginx \
    gettext-base \
    # ... PHP extensions

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# CMD is not needed - entrypoint handles everything
```

### Railway config

#### railway.json
```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  },
  "deploy": {
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

### Environment Variables (Railway)

Wymagane zmienne środowiskowe:
- `APP_KEY` - klucz aplikacji Laravel
- `DB_HOST` - host bazy danych
- `DB_PORT` - port bazy danych
- `DB_DATABASE` - nazwa bazy danych
- `DB_USERNAME` - użytkownik bazy danych
- `DB_PASSWORD` - hasło bazy danych
- `PORT` - automatycznie ustawiane przez Railway (nie trzeba ręcznie)

### Debugging

#### Sprawdzanie logów
```bash
railway logs --service delegacje --tail 200
```

#### Sprawdzanie procesów w kontenerze
```bash
# UWAGA: ps wymaga procps - jeśli nie ma, użyj alternatyw:
railway run bash -c "ps aux"  # tylko jeśli procps jest zainstalowany
railway run bash -c "cat /proc/1/status"  # alternatywa bez procps
railway run bash -c "pgrep -a nginx"  # alternatywa bez procps
```

#### Sprawdzanie portów
```bash
railway run bash -c "ss -tlnp | grep -E '(80|8080)'"
```

#### Test konfiguracji Nginx
```bash
railway run bash -c "nginx -t"
```

### Źródła problemów

1. **Przeinżynierowanie** - entrypoint robił za dużo
2. **Brak zrozumienia Railway** - Railway potrzebuje PID 1 = HTTP server
3. **Narzędzia których nie ma** - używanie `ps` bez `procps`
4. **Migracje w entrypoint** - powinny być osobno
5. **Setup w runtime** - powinien być w build/CI/CD

### Podsumowanie

**Co działa:**
- ✅ Minimalny entrypoint
- ✅ Nginx jako PID 1 (w planie i zaimplementowane)
- ✅ PHP-FPM w tle (w planie i zaimplementowane)
- ✅ Dynamic PORT handling (envsubst działa)
- ✅ Konfiguracja Nginx jest poprawna (test przechodzi)
- ✅ PHP-FPM startuje i jest gotowy

**Co nie działa / w trakcie:**
- ⚠️ Nginx startuje ("Starting Nginx..."), ale brak potwierdzenia, że odpowiada
- ⚠️ Aplikacja zwraca 502 Bad Gateway
- ❌ Migracja bazy danych (payroll_id NOT NULL) - BLOKADA PRODUCTION
- ❌ Setup aplikacji (storage:link, cache) - musi być w CI/CD

**Co trzeba zrobić:**
- Zdiagnozować, dlaczego Nginx nie odpowiada (może nasłuchuje, ale nie loguje?)
- Naprawić migrację bazy danych
- Przenieść setup do CI/CD
- Potwierdzić, że Nginx faktycznie nasłuchuje na porcie 8080 i odpowiada na requesty
