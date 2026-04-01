
---

## 4. Advanced Production Fixes (Crucial for Deployment)

### 4.1 Apache .htaccess Routing
Since Vue SPA and Laravel live in the same root folder, Apache must know how to route everything correctly. The web/.htaccess file must be configured perfectly so images/avatars work, API works, and Vue Router works:
\\\pache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # 1. ALLOW EXISTING PHYSICAL FILES FIRST (e.g. storage/avatars, assets)
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} -f [OR]
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} -d
    RewriteRule ^ - [L]

    # 2. ROUTE LARAVEL API AND SANCTUM TO INDEX.PHP
    RewriteCond %{REQUEST_URI} ^/api/ [OR]
    RewriteCond %{REQUEST_URI} ^/sanctum/
    RewriteRule ^ index.php [L]

    # 3. ROUTE EVERYTHING ELSE TO VUE SPA (index.html)
    RewriteRule ^ index.html [L]
</IfModule>
\\\

### 4.2 Laravel CORS Configuration
In \core/config/cors.php\, update the paths and credentials to let Sanctum breathe:
\\\php
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://gportal.fpvucm.sk'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // CRITICAL FOR SANCTUM
\\\

### 4.3 Vue Axios & CSRF Configurations
Sanctum requires strict frontend alignment. In \rontend/src/main.js\, we made these patches:

1. **Axios Defaults**:
\\\javascript
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
\\\

2. **Explicit CSRF Request Before Login** (in \LoginView.vue\):
\\\javascript
// Dôrazne požiadame Sanctum o inicializáciu CSRF cookie pred loginom
await axios.get(\\/sanctum/csrf-cookie\, { withCredentials: true });
const response = await axios.post(\\/api/login\, {...});
\\\

3. **Fetch API Interceptor** (in \main.js\):
Because the Admin panel heavily relied on the native \etch()\ API instead of Axios, we had to patch \window.fetch\ to ensure it intercepts \XSRF-TOKEN\ cookies and sends them as \X-XSRF-TOKEN\ headers with \credentials: 'include'\. Without this, admin actions (PUT/DELETE/POST) failed with 419 / CSRF Token Mismatch.
