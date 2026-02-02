# ⚡ Szybki Deploy - 5 minut

## 🚀 Railway (Zalecane)

### 1. Push do GitHub
```bash
git add .
git commit -m "Add deployment config"
git push origin main
```

### 2. Railway Setup (3 minuty)
1. Idź na [railway.app](https://railway.app) → Zaloguj przez GitHub
2. **New Project** → **Deploy from GitHub** → Wybierz `delegacje`
3. **New** → **Database** → **MySQL** (Railway automatycznie doda zmienne DB)
4. **Variables** → Dodaj:
   ```
   APP_KEY=base64:WYGENERUJ_PRZEZ: php artisan key:generate --show
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://twoja-aplikacja.up.railway.app
   ```
5. **Settings** → Skopiuj URL i wklej jako `APP_URL`

### 3. Gotowe! 🎉
- Railway automatycznie zbuduje i wdroży
- HTTPS działa automatycznie
- Po deployu: Railway → Terminal → `php artisan migrate --force`

---

## 📖 Pełna instrukcja
Zobacz [DEPLOY.md](DEPLOY.md) dla szczegółów.
