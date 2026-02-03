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

**Problem:** Railway wymaga, żeby główny proces (PID 1) nasłuchiwał na porcie HTTP.

**Próby naprawy:**
- Przełączenie z Unix socket na TCP (127.0.0.1:9000) dla PHP-FPM
- Zmiana uprawnień (nginx:nginx vs www-data:www-data)
- Przełączenie z Alpine na Ubuntu (prostsze uprawnienia)

**Rozwiązanie:** Usunięcie Supervisora, Nginx jako PID 1, PHP-FPM w tle.

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
- Migracje w entrypoint (powinny być osobno)
- `storage:link` przy każdym starcie
- Cache clear/cache przy każdym starcie
- Sprawdzanie bazy danych w pętli
- Użycie `ps` bez zainstalowanego pakietu
- Uruchamianie Nginx w tle (`&`) zamiast `exec`

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

**Status:** Nie naprawione - migracja jest ignorowana (`|| echo "Migration failed, continuing..."`), ale powinna być naprawiona osobno.

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

1. **Railway wymaga PID 1 = proces HTTP**
   - Główny proces musi nasłuchiwać na `${PORT}`
   - Supervisor nie działa jako PID 1 dla HTTP

2. **Entrypoint powinien być minimalny**
   - Tylko start procesów
   - Setup/migracje powinny być osobno (CI/CD, cron, init containers)

3. **PORT jest dynamiczny**
   - Railway przypisuje losowy port
   - Musi być podmieniony w konfiguracji przed startem

4. **Static test first**
   - Najpierw udowodnij, że port jest otwarty (static 200 OK)
   - Potem dodawaj PHP/Laravel

5. **Nie używaj narzędzi, których nie ma**
   - `ps` wymaga `procps`
   - W kontenerach minimalnych może nie być standardowych narzędzi

### Obecny stan

**Działa:**
- ✅ Build przechodzi
- ✅ Nginx startuje jako PID 1
- ✅ PORT jest podmieniany
- ✅ PHP-FPM startuje w tle

**Do naprawy:**
- ⚠️ Migracja bazy danych (payroll_id NOT NULL)
- ⚠️ Setup aplikacji (storage:link, cache) - powinno być w CI/CD lub init script
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
    
    access_log /dev/stdout;
    error_log /dev/stderr;
    
    location / {
        return 200 "OK\n";
        add_header Content-Type text/plain;
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
CMD ["nginx", "-g", "daemon off;"]
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
railway run bash -c "ps aux"
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
- Minimalny entrypoint
- Nginx jako PID 1
- Dynamic PORT handling
- PHP-FPM w tle

**Co trzeba zrobić:**
- Naprawić migrację bazy danych
- Przenieść setup do CI/CD
- Przywrócić pełną konfigurację Laravel (po potwierdzeniu, że static działa)
