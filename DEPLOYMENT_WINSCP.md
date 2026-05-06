# Deployment & Local Development Guide (WinSCP / Shared Hosting)

---

## 0. Server Specifications (gportal.fpvucm.sk) — verified 2026-04-06

| Property | Value |
|---|---|
| **OS** | Red Hat Enterprise Linux 8.10 |
| **PHP version** | 8.2.30 (FPM/FastCGI) |
| **Server** | Apache |
| **Web root** | `/srv/saa/gportal/web/` |
| **`open_basedir`** | `/srv/saa/gportal/web/:/var/tmp/` |
| **`upload_max_filesize`** | 256 MB |
| **`post_max_size`** | 256 MB |
| **`max_execution_time`** | 180 seconds |
| **`memory_limit`** | 1560 MB |
| **`max_file_uploads`** | 50 |
| **ZipArchive** | enabled (zip extension v1.22.8, libzip 1.11.4) |
| **GD / ImageMagick** | enabled |
| **MySQL** | mysqlnd 8.2.30 |

### Disabled PHP functions on server
The following functions are blocked by `disable_functions` — do not use them in backend code:
`exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `escapeshellarg`, `escapeshellcmd`, `symlink`, `link`, `readlink`, `dl`

> **Impact:** Use PHP's native classes instead of shell commands. For example, use `ZipArchive` (not `unzip` via `exec`), and do NOT use `php artisan storage:link` equivalent in code (symlinks are blocked). The existing storage workaround in `config/filesystems.php` already handles this correctly.

---

This document tracks the exact steps, environment configurations, and workarounds needed to deploy the Game Registration Portal to a restricted university server (where you only have access to a single `web` folder) and how to smoothly switch back to local development.

---

## 1. Environment Configurations (`.env` Tracking)

You need to maintain two distinct configurations. Never overwrite your local `.env` with the production one.

### 🔴 Production (University Server)
**Backend `.env`:**
```ini
APP_NAME=GamePortal
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gportal.fpvucm.sk

FRONTEND_URL=https://gportal.fpvucm.sk
SANCTUM_STATEFUL_DOMAINS=gportal.fpvucm.sk
SESSION_DOMAIN=.gportal.fpvucm.sk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gportal
DB_USERNAME=gportal                          # Assuming username is same as DB, verify!
DB_PASSWORD=PROGENY-reliable-2549
```

**Frontend `.env.production` (Create this in `frontend/`):**
```ini
# Note: DO NOT add a trailing /api to VITE_API_URL. Axios takes care of it.
VITE_API_URL=https://gportal.fpvucm.sk
VITE_BASE_URL=https://gportal.fpvucm.sk
```

### 🟢 Local Development
**Backend `.env`:**
```ini
APP_NAME=GamePortal
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_local_db_name
DB_USERNAME=root
DB_PASSWORD=
```

**Frontend `.env.local` (Create this in `frontend/`):**
```ini
VITE_API_URL=http://localhost:8000
VITE_BASE_URL=http://localhost:5173
```
> ⚠️ Do NOT add `/api` to `VITE_API_URL` locally. The frontend appends `/api/` itself when building request URLs.

---

## 2. Deployment Steps (WinSCP Restricted Folder Workflow)

Since you cannot put files *outside* the `web` folder, you cannot use the traditional Laravel structure (where the core application sits securely above the public document root). You must use the "Shared Hosting Workaround."

### Step 2.1: Prepare Backend for Shared Hosting
*This step extracts Laravel's core code from the public entry point so we can safely deploy everything into one folder.*

1. **Clear Local Cache:** In your local `backend` terminal, run this so you don't accidentally upload cached absolute file paths meant for your local computer:
   ```bash
   cd backend
   php artisan optimize:clear
   ```

2. **Create the Production `.env`:** Temporarily edit your `backend/.env` file so it contains the university credentials. See the **Production (University Server)** section above for exactly what to copy and paste.

3. **Create the "Core" Folder:** Open PowerShell or Command Prompt and create a new staging folder on your desktop called `web-upload`, and a folder inside it called `core`:
   ```bash
   mkdir $HOME\Desktop\web-upload
   mkdir $HOME\Desktop\web-upload\core
   ```

4. **Move Source Files:** Copy everything from your local `backend` folder into `web-upload/core` **EXCEPT** the `public` folder. You can do this manually in File Explorer, or using this command (run from your project root):
   ```bash
   Robocopy .\backend $HOME\Desktop\web-upload\core /E /XD public
   ```

5. **Secure the Core:** Create a file named `.htaccess` inside `web-upload/core` to prevent anyone from reading your `.env` or source code from their browser:
   ```bash
   echo "Deny from all" > $HOME\Desktop\web-upload\core\.htaccess
   ```

6. **Move Public Files:** Copy the contents of your local `backend/public` folder and paste them directly into the root of `web-upload`:
   ```bash
   Robocopy .\backend\public $HOME\Desktop\web-upload /E
   ```

7. **Modify `index.php`:** Open the newly copied file `$HOME/Desktop/web-upload/index.php` in VS Code and update the paths to point to the `core` folder. Change these two lines:
   ```php
   // Change from: require __DIR__.'/../vendor/autoload.php';
   require __DIR__.'/core/vendor/autoload.php';

   // Change from: $app = require_once __DIR__.'/../bootstrap/app.php';
   $app = require_once __DIR__.'/core/bootstrap/app.php';
   ```

### Step 2.2: Prepare Frontend
1. In your local terminal, navigate to the `frontend` folder.
2. Run `npm run build`.
3. This creates a `dist` folder containing your compiled Vue application (HTML, JS, CSS).

### Step 2.3: Upload via WinSCP
1. Open WinSCP and connect to your university server.
2. Navigate into the `web` folder.
3. Upload the **contents** of your modified `backend` folder (which now has `index.php` and `backend-core`) directly into the `web` folder.
4. Upload the **contents** of your Vue `frontend/dist` folder into the `web` folder as well.
5. *Conflict Resolution:* If both Laravel and Vue have an `index.php`/`index.html`, ensure the server is configured to serve `index.html` (Vue) by default, OR integrate the Vue `index.html` into a Laravel Blade view.

### Step 2.4: Database Setup
1. Use a tool like phpMyAdmin (if provided by the uni) or an SSH tunnel in DBeaver/DataGrip to connect to the database.
2. Access DB `gportal` with user and password `PROGENY-reliable-2549`.
3. Export your local database schema using `mysqldump` or your DB tool.
4. Import the schema to the remote `gportal` database.

---

## 3. How to Switch Between Modes

### Going to Local Dev:
1. Ensure your local `backend/.env` is set to local credentials.
2. Run `php artisan storage:link` (only needed once, or after a fresh clone — creates the symlink that makes uploaded files accessible locally).
3. Run `php artisan serve` (Backend).
4. Run `npm run dev` (Frontend).
5. Use `localhost:5173` in your browser.

> **Why storage:link is needed locally but not on the server:**
> Locally, `php artisan serve` uses PHP's built-in web server which serves files directly from `backend/public/`. `storage:link` creates a symlink `backend/public/storage → backend/storage/app/public`, so uploaded files become accessible at `localhost:8000/storage/...` automatically.
> On the server there is no SSH to run `storage:link`, so instead `config/filesystems.php` is configured to store uploaded files directly into `~/web/storage/` (the web root), where Apache serves them as real files. See section 4.4.

### Going to Production (Deploying Updates):
1. Test everything locally first.
2. Ensure `frontend/.env.production` is correctly pointing to your university domain.
3. Rebuild frontend: `npm run build`.
4. Open WinSCP. Drag the new frontend `dist` files and any updated backend files (like controllers in `backend-core/app/...`) over to overwrite the old ones in the `web` folder.
5. If you ran migrations locally, you must manually run or mirror those SQL changes on the production database, as you likely won't have SSH access to run `php artisan migrate` directly on the strict university server.
---

## 4. Advanced Production Fixes (Crucial for Deployment)

### 4.1 Apache `.htaccess` Routing
Since Vue SPA and Laravel live in the same root folder, Apache must know how to route everything correctly. The `web/.htaccess` file must be configured perfectly so images/avatars work, API works, videos play, and Vue Router works:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization / CSRF Headers
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # IF the request is for a real file (like an image in /storage), stop here and SERVE IT DIRECTLY!
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]

    # IF the request is for /api, /sanctum, /video, or /storage, send to Laravel (index.php)
    # NOTE: /login and /logout are Vue routes — do NOT add them here or page refresh returns 404
    RewriteCond %{REQUEST_URI} ^/api/ [OR]
    RewriteCond %{REQUEST_URI} ^/sanctum/ [OR]
    RewriteCond %{REQUEST_URI} ^/video/ [OR]
    RewriteCond %{REQUEST_URI} ^/storage/
    RewriteRule ^ index.php [L]

    # OTHERWISE (Vue frontend routing), if it's not a real folder, load index.html!
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
    
    # Set priority
    DirectoryIndex index.html index.php
</IfModule>
```

### 4.2 Laravel CORS Configuration
In `core/config/cors.php`, update the paths and credentials to let Sanctum breathe:
```php
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://gportal.fpvucm.sk'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // CRITICAL FOR SANCTUM
```

### 4.3 File Storage — Shared Hosting Workaround

On a standard Laravel server you run `php artisan storage:link` to create a symlink from `public/storage` to `storage/app/public`. This is impossible here without SSH.

**The problem without a symlink:**
- Laravel stores uploaded files (splash screens, videos, etc.) in `~/web/core/storage/app/public/`
- That path is inside `core/` which is not directly web-accessible
- Requests to `https://gportal.fpvucm.sk/storage/file.png` return 404

**The fix already applied in this codebase:**

1. **`backend/config/filesystems.php`** — in production, the public disk stores files directly into `~/web/storage/` (the web root) instead of `~/web/core/storage/app/public/`:
   ```php
   'public' => [
       'root' => env('APP_ENV') === 'production' ? base_path('../storage') : storage_path('app/public'),
       ...
   ]
   ```
   `base_path('../storage')` resolves to `~/web/core/../storage` = `~/web/storage/`. Apache's `-f` rule then serves these files directly without any PHP involved.

2. **`backend/routes/web.php`** — a `/storage/{path}` fallback route serves any files that ended up in the old `core/storage/app/public/` location:
   ```php
   Route::get('/storage/{path}', function (string $path) { ... })->where('path', '.*');
   ```

3. **`~/web/.htaccess`** (server only, not in repo) — must include `/storage/` in the Laravel routing block (see section 4.1 above). Without this line, `/storage/` requests get served `index.html` instead of PHP.

**If uploaded files ever stop showing (404 on images):**
1. Check `~/web/.htaccess` has `RewriteCond %{REQUEST_URI} ^/storage/ [OR]` in the routing block
2. Check `~/web/storage/` directory exists on the server (WinSCP)
3. Check `config/filesystems.php` has the production conditional for `root`
4. If a file is in the wrong location (`~/web/core/storage/app/public/...`), move it manually via WinSCP to `~/web/storage/...` preserving the subfolder structure

**Why local dev works without any of this:**
`php artisan storage:link` creates `backend/public/storage → backend/storage/app/public`. PHP's built-in server (`artisan serve`) serves the symlinked files as static files automatically. `APP_ENV=local` so `filesystems.php` uses the standard `storage_path('app/public')` path.

---

### 4.4 Vue Axios & CSRF Configurations
Sanctum requires strict frontend alignment. In `frontend/src/main.js`, we make these patches:

1. **Axios Defaults**:
```javascript
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
```

2. **Explicit CSRF Request Before Login** (in `LoginView.vue`):
```javascript
// Explicitly ask Sanctum to generate the CSRF cookie before login
await axios.get(`${API_URL}/sanctum/csrf-cookie`, { withCredentials: true });
const response = await axios.post(`${API_URL}/api/login`, {...});
```

3. **Fetch API Interceptor** (in `main.js`):
Because the Admin panel heavily relied on the native `fetch()` API instead of Axios, we had to patch `window.fetch` to ensure it intercepts `XSRF-TOKEN` cookies and sends them as `X-XSRF-TOKEN` headers with `credentials: 'include'`. Without this, admin actions (PUT/DELETE/POST) failed with 419 / CSRF Token Mismatch.
```javascript
const originalFetch = window.fetch
window.fetch = async function(url, options = {}) {
  options.credentials = 'include'
  
  if (options.method && !['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(options.method.toUpperCase())) {
    options.headers = options.headers || {}
    if (!(options.headers instanceof Headers)) {
      const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'))
      if (match) {
        options.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[2])
      }
      options.headers['Accept'] = 'application/json'
    } else {
      const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]*)'))
      if (match) {
        options.headers.append('X-XSRF-TOKEN', decodeURIComponent(match[2]))
      }
      options.headers.append('Accept', 'application/json')
    }
  }
  return originalFetch(url, options)
}
```
