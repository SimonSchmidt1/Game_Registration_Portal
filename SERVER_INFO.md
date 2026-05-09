# Production Server — gportal.fpvucm.sk

Diagnostické údaje zachytené z `phpinfo()` priamo na produkčnom hostingu.
Slúži ako referencia pre debugging, plánovanie limitov a pri komunikácii so správcom servera.

---

## Hosting

| Položka | Hodnota |
|---|---|
| Doména | https://gportal.fpvucm.sk |
| SSH host / port | `gportal.fpvucm.sk` / `61620` |
| SSH user | `gportal` |
| Web root | `/srv/saa/gportal/web/` |
| OS | RHEL 8.10 (Linux saa, 4.18.0-553.120.1.el8_10) |
| DB | MySQL/MariaDB (mysqlnd 8.2.31) |

## PHP

| Položka | Hodnota |
|---|---|
| Verzia | **PHP 8.2.31** |
| SAPI | **FPM/FastCGI** |
| Build | Remi's RPM repository |
| Zend OPcache | Enabled (memory_consumption 512M, JIT tracing dostupné no `jit_buffer_size=0` → reálne JIT vypnuté) |
| ImageMagick | 6.9.13-25 Q16 (imagick 3.8.1) |

### Kľúčové ini hodnoty (Local = Master, hard-cap)

| Direktíva | Hodnota | Pozn. |
|---|---|---|
| `upload_max_filesize` | **256M** | Hard-cap — `.user.ini` ho neprekročí |
| `post_max_size` | **256M** | Hard-cap |
| `memory_limit` | 1560M | |
| `max_execution_time` | 180 | |
| `max_input_time` | 180 | |
| `max_file_uploads` | 50 (Local) / 20 (Master) | `.user.ini` overrides → **funguje** |
| `max_input_vars` | 1000 | |
| `default_charset` | UTF-8 | |
| `date.timezone` | UTC | Default |
| `open_basedir` | `/srv/saa/gportal/web/:/var/tmp/:` | Filesystem sandbox |
| `upload_tmp_dir` | `/var/tmp` | |

### Zakázané PHP funkcie (security)

`exec, shell_exec, system, passthru, popen, proc_open, proc_close, proc_terminate, proc_get_status, proc_nice, dl, link, symlink, readlink, escapeshellarg, escapeshellcmd, openlog, syslog, define_syslog_variables, register_tick_function, leak, show_source, apache_child_terminate, apache_setenv, diskfreespace, disk_free_space, disk_total_space, pfsockopen, posix_getgrnam, posix_getpgid, posix_getpwuid, posix_getpwnam, posix_getrlimit, posix_initgroups, posix_kill, posix_mkfifo, posix_mknod, posix_setpgid, posix_setsid, posix_setuid, posix_uname`

> Pri implementácii nových features over že nepoužívame žiadnu z týchto funkcií. Najmä `exec/shell_exec/proc_open` sú zakázané — žiadne externé príkazy z PHP. ImageMagick (cez extension) je OK.

### Aktívne extensions (relevantné)

`bcmath, curl, dom, exif, fileinfo, ftp, gd (FreeType, JPEG, PNG, GIF, WebP, AVIF), gettext, iconv, imagick, intl (ICU 74.2), mbstring, mcrypt, mysqli, mysqlnd, openssl (1.1.1k FIPS), pdo_mysql, pdo_sqlite, phar, posix, session, sodium, sqlite3, tokenizer, xml, xmlreader, xmlwriter, xsl, zip (1.22.8 / libzip 1.11.4), zlib`

---

## Upload limity v kóde (po refaktore 2026-05-07)

Tieto limity sú nastavené v kóde (frontend FileUpload + Laravel `ProjectController` validácia + `.user.ini`). Aby fungovali v prevádzke, **správca hostingu musí zdvihnúť PHP-FPM master cap** zo 256 MB — viď nižšie.

| Field | Per-file limit (kód) |
|---|---|
| splash_screen | 15 MB |
| video | 1 GB |
| documentation | 70 MB |
| presentation | 50 MB |
| source_code | 1,5 GB |
| export | 3 GB |
| apk_file | 512 MB |
| ios_file | 512 MB |
| webgl_build | 250 MB (zip), 500 MB nekomprimovane |

Celková obálka jedného POST requestu (suma všetkých polí): **~5,6 GB** (univerzálny projekt) až **~6,6 GB** (mobile_app).

### Čo musí zdvihnúť správca hostingu

V PHP-FPM pool configu (`/etc/php-fpm.d/<pool>.conf` alebo cez `php_admin_value`):
- `upload_max_filesize = 3500M` (najväčší jeden súbor = 3 GB export, +rezerva)
- `post_max_size = 6000M` (celkový POST envelope)
- `max_execution_time = 3600` (1 h pre pomalé linky)
- `max_input_time = 3600`

V Apache (httpd.conf alebo .htaccess kde je povolené):
- `LimitRequestBody 6442450944` (6 GB v bajtoch) alebo úplne odstrániť limit

Po zmene: `systemctl reload php-fpm && systemctl reload httpd`

### Riešenia pre väčšie súbory

1. **Kontaktuj správcu** — Požiadaj IT FPV UCM o zvýšenie `upload_max_filesize` a `post_max_size` v PHP-FPM pool configu (`/etc/php-fpm.d/`). Spomeň aj `LimitRequestBody` v Apache (ak je nastavený).
2. **Chunked upload** — Vyžaduje nový backend endpoint ktorý prijíma súbor po častiach (resumable.js, tus.io). Komplikované ale obíde server limity.
3. **Externé úložisko** — User by uploadoval mimo (Google Drive, OneDrive) a do projektu by sa pridal len odkaz.

---

## Diagnostické tipy

- **`phpinfo()` na overenie limitov** — vytvor `backend/public/phpinfo.php` s `<?php phpinfo(); ?>`, otvor v prehliadači, **HNED PO OVERENÍ VYMAŽ** (verejné prezradenie konfigurácie servera).
- **`.user.ini` cache TTL = 300s** — po zmene počkaj 5 minút než sa hodnota prejaví, alebo zmeň `user_ini.cache_ttl` (zvyčajne nemožné cez `.user.ini` samotný).
- **Hard-cap detekcia** — Ak Local Value === Master Value v phpinfo a tvoj `.user.ini` má inú hodnotu, hosting má hard-cap.
- **Apache `LimitRequestBody`** — Cez phpinfo nezistíš. Detekuje sa iba reálnym uploadom: ak request zlyhá s **HTTP 413** ešte pred PHP, je to Apache.
