# UCO Trace Ghana — Phase 1 (Field Data Capture)

Monorepo containing:
- `backend/` — Laravel 12 + MySQL/MariaDB + Sanctum REST API + Custom Blade/Tailwind Admin Console (cPanel-ready, pre-built `public/build/` assets included)
- `mobile/` — Flutter Offline-First Field Collector App (SQLite queue, background sync, QR scan + unreadable QR fallback, non-editable GPS, live camera SHA-256 evidence)

## cPanel Deployment (from Git)

1. Point your subdomain Document Root to `backend/public`.
2. In `backend/`, copy `.env.example` to `.env`, set `APP_URL`, cPanel MySQL credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), and your desired `ADMIN_SEED_EMAIL` / `ADMIN_SEED_PASSWORD`.
3. In cPanel **MultiPHP INI Editor**, set PHP 8.2+ and `upload_max_filesize = 25M`, `post_max_size = 30M`.
4. In cPanel **Terminal** inside `backend/`:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --seed --force
   php artisan config:cache
   php artisan route:cache
   ```

## Flutter Mobile App

```bash
cd mobile
flutter pub get
flutter run
```
On the Login screen, tap the top-right settings gear icon to point the app to your local IP or live cPanel API URL (`https://yourdomain.com/api/v1`).
