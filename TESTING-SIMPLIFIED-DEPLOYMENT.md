# Testing Simplified Railway Deployment

## What Changed

### Before → After:
- **Entrypoint**: 270 lines → 56 lines (40 code lines) - **79% reduction**
- **Dockerfile**: 112 lines → 66 lines (34 code lines) - **41% reduction**
- **Removed**: JSON debug logs, hypothesis IDs, triple APP_KEY verification, 50+ lines of symlink testing
- **Kept**: Volume support, APP_KEY validation, .env removal, optional migrations

### Security:
- ✅ **No APP_KEY in logs** (only validation, never the actual key)
- ✅ No debug bloat
- ✅ No hypothesis tracking

---

## Local Testing

### 1. Build the image:
```bash
docker build -t delegacje-test .
```

### 2. Test basic run (no volume):
```bash
docker run --rm \
  -e APP_KEY=base64:ALdMPGMday9FaJ1OseM4DPksFmy7A2W0R8Zgtky4OSI= \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e PORT=8000 \
  -p 8000:8000 \
  delegacje-test
```

**Expected output:**
```
Starting Laravel on Railway (Port: 8000)
INFO  Configuration cache cleared successfully.
INFO  Route cache cleared successfully.
INFO  Compiled views cleared successfully.
INFO  Application cache cleared successfully.
Server starting on 0.0.0.0:8000

   INFO  Server running on [http://0.0.0.0:8000].
```

**Test in browser:** http://localhost:8000

---

### 3. Test with Railway Volume (persistent storage):
```bash
# Create test volume directory
mkdir -p test-volume

# Run with volume mounted
docker run --rm \
  -e APP_KEY=base64:ALdMPGMday9FaJ1OseM4DPksFmy7A2W0R8Zgtky4OSI= \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e PORT=8000 \
  -p 8000:8000 \
  -v $(pwd)/test-volume:/data \
  delegacje-test
```

**Expected additional output:**
```
Railway Volume detected at /data
Initializing volume (first run)...
Volume configured for persistent storage
```

**Verify files were copied:**
```bash
ls test-volume/storage/app/public/
# Should show: employees/ users/ vehicles/ accommodations/ etc.
```

**Test persistence:**
1. Upload a file through the app
2. Stop container (Ctrl+C)
3. Start container again with same volume
4. File should still be there

---

### 4. Test migrations (optional):
```bash
docker run --rm \
  -e APP_KEY=base64:ALdMPGMday9FaJ1OseM4DPksFmy7A2W0R8Zgtky4OSI= \
  -e RUN_MIGRATIONS=true \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=delegacje \
  -e DB_USERNAME=sail \
  -e DB_PASSWORD=password \
  -e PORT=8000 \
  -p 8000:8000 \
  delegacje-test
```

**Expected output:**
```
Running migrations...
Nothing to migrate.
```

---

## Deploy to Railway

### 1. Commit changes:
```bash
git add docker/entrypoint-railway.sh Dockerfile railway.json
git commit -m "Simplify deployment: 270→56 lines entrypoint, remove debug bloat

- Remove JSON debug logs, hypothesis IDs, triple APP_KEY verification
- Simplify volume setup (preserve first-run initialization)
- Move server.php to Dockerfile build stage
- Increase healthcheck timeout to 30s
- No security issues: APP_KEY never logged

Backups saved as *.old files"
```

### 2. Push to GitHub:
```bash
git push origin main
```

### 3. Watch Railway build logs:
```bash
railway logs --follow
```

**Expected clean output:**
```
Starting Laravel on Railway (Port: 9000)
Railway Volume detected at /data
Volume configured for persistent storage
INFO  Configuration cache cleared successfully.
INFO  Route cache cleared successfully.
INFO  Compiled views cleared successfully.
INFO  Application cache cleared successfully.
Server starting on 0.0.0.0:9000

   INFO  Server running on [http://0.0.0.0:9000].
```

---

## Rollback (if needed)

If something goes wrong:

```bash
# Restore old files
cp docker/entrypoint-railway.sh.old docker/entrypoint-railway.sh
cp Dockerfile.old Dockerfile
cp railway.json.old railway.json

# Commit and push
git add -A
git commit -m "Rollback to previous deployment config"
git push origin main
```

---

## Success Checklist

- ✅ Build succeeds locally
- ✅ Container starts without errors
- ✅ Healthcheck endpoint responds: `curl http://localhost:8000/api/health`
- ✅ Volume persistence works (files survive container restart)
- ✅ No APP_KEY in logs (security)
- ✅ No JSON debug logs (clean output)
- ✅ Railway deployment succeeds
- ✅ App works identically to before

---

## Comparison: Old vs New Logs

### Old (bloated):
```
+ date +%s
+ echo {"id":"log_1770840803_railway_key","timestamp":1770840803000...
🔍 Pre-flight checks...
DEBUG: .env file DOES NOT EXIST
DEBUG: Railway env var APP_KEY: base64:ALdM...
DEBUG: Laravel config('app.key') after .env removal: base64:ALdM...
✅ VERIFIED: Laravel uses Railway env var (keys match)
   Symlink points to: /data/storage/app/public
   ✅ Symlink target is correct
   ✅ Write test through symlink: SUCCESS
[... 50 more lines ...]
```

### New (clean):
```
Starting Laravel on Railway (Port: 9000)
Railway Volume detected at /data
Volume configured for persistent storage
INFO  Configuration cache cleared successfully.
INFO  Route cache cleared successfully.
INFO  Compiled views cleared successfully.
INFO  Application cache cleared successfully.
Server starting on 0.0.0.0:9000

   INFO  Server running on [http://0.0.0.0:9000].
```

**Log reduction: ~70 lines → ~8 lines**

---

## Notes

- **Backups**: Old files saved as `.old` in case rollback is needed
- **No breaking changes**: All functionality preserved (Volume, migrations, healthcheck)
- **Security improved**: APP_KEY never logged (was showing first 30 chars before)
- **Maintenance**: Much easier to understand and modify (40 lines vs 270)
