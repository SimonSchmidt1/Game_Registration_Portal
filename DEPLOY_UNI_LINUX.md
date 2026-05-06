> [!WARNING]
> **THIS DOCUMENT IS DEPRECATED.** 
> For the new, tested Shared Hosting Workaround (including full setup for .htaccess, Sanctum CSRF fixes, and WinSCP deployment), please refer to the `DEPLOYMENT_WINSCP.md` file.

# Deployment Guide (UCM Linux Server + WinSCP)

This guide is tailored to your server:
- Domain: `https://gportal.fpvucm.sk`
- SSH host: `gportal.fpvucm.sk`
- SSH port: `61620`
- SSH user: `gportal`
- Web root folder: `web`
- DB host: `localhost`
- DB name/user: `gportal`

---

## A) Fast profile switch (local vs production)

Use these values before each mode.

### Local development mode

`backend/.env`

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
FRONTEND_URL=http://127.0.0.1:5173

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=game_portal
DB_USERNAME=root
DB_PASSWORD=

SESSION_DOMAIN=null
```

`frontend/.env`

```env
VITE_API_URL=http://127.0.0.1:8000
VITE_ADMIN_EMAIL=admin@gameportal.local
```

Run local:

```powershell
cd backend
php artisan serve

# new terminal
cd frontend
npm run dev
```

### Production build mode (for your university host)

`backend/.env` (on server)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gportal.fpvucm.sk
FRONTEND_URL=https://gportal.fpvucm.sk

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=gportal
DB_USERNAME=gportal
DB_PASSWORD=PROGENY-reliable-2549

SESSION_DOMAIN=gportal.fpvucm.sk
```

If DB login fails, try:

```env
DB_PASSWORD=SNAD-dobre-napisane-2
```

`frontend/.env` (local machine before `npm run build`)

```env
VITE_API_URL=https://gportal.fpvucm.sk/backend
```

---

## B) Exact WinSCP deployment flow (permission-limited account)

Because you cannot create folders next to `web` (Permission denied), use this structure:

- `~/web/` -> frontend static build (`frontend/dist/*`)
- `~/web/backend/` -> contents of local `backend/public`
- `~/web/backend_app/` -> full backend app from local `backend` **except** `public`

### Step-by-step

1. Build frontend locally:

```powershell
cd frontend
npm install
npm run build
```

2. In WinSCP remote `web`, create folders:
   - `backend`
   - `backend_app`

3. Upload local `frontend/dist/*` to remote `web/`.

4. Upload local `backend/public/*` to remote `web/backend/`.

5. Upload local `backend/*` (except `public`) to remote `web/backend_app/`.

---

## C) Backend entrypoint for this exact layout

Edit remote file `~/web/backend/index.php` and use:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../backend_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../backend_app/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../backend_app/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

---

## D) SSH finalize commands

Connect:

```bash
ssh -p 61620 gportal@gportal.fpvucm.sk
```

Run:

```bash
cd ~/web/backend_app
php -v
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

---

## E) Database import (if needed)

If DB is empty:

```bash
mysql -h localhost -u gportal -p gportal < backup.sql
```

Try password in this order:
1. `PROGENY-reliable-2549`
2. `SNAD-dobre-napisane-2`

---

## F) Verification checklist

- Frontend loads: `https://gportal.fpvucm.sk`
- API responds: `https://gportal.fpvucm.sk/backend/api/projects`
- Login works and token is stored
- Team/project actions work
- Uploaded files are accessible

---

## G) Common failure points

- `404` on API: wrong `VITE_API_URL` or missing `~/web/backend/.htaccess`
- `500` on API: wrong paths in `~/web/backend/index.php`
- CORS errors: `FRONTEND_URL` in backend `.env` not set to production domain
- DB errors: wrong `DB_*` values
