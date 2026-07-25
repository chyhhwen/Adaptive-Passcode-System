# Adaptive Passcode System

A small PHP application built to practise implementing web security controls
from scratch — routing, authentication, and the defences listed below — without
a framework.

## YouJia

![](https://github.com/chyhhwen/route-php/blob/main/youjia.gif?raw=true)

## Features

- **Routing** — `parse_url()` based route table mapping HTTP method + path to a handler
- **Authentication** — registration and login with bcrypt password hashing (`password_hash` / `password_verify`), automatic rehash when the cost factor changes
- **Session management** — `HttpOnly` / `SameSite` cookies, ID regeneration on login, logout
- **Adaptive login throttling** — lockout thresholds that escalate with an address's failure rate
- **JSON API** — authenticated endpoints for reading and deleting picture records
- **Configuration** — all credentials read from `.env`, nothing hardcoded

## Defense

**Cross-Site Scripting**
All template output is escaped through `View::e()` (`htmlspecialchars` with
`ENT_QUOTES`), and a Content-Security-Policy header blocks inline scripts.

**SQL Injection**
Every query uses parameterised prepared statements with
`PDO::ATTR_EMULATE_PREPARES` disabled, so values are bound server-side rather
than interpolated into the SQL string. API ids are validated as integers.

**Brute-force Attack**
Two-tier throttle: 5 failed logins within 15 minutes lock the address out
(HTTP 429), and continued attempts up to 25 failures add it to a permanent
blocklist that rejects every route.

**Cross-Site Request Forgery**
A per-session token on every state-changing form and endpoint, compared with
`hash_equals()`.

**Session fixation**
`session_regenerate_id(true)` on successful login.

Failure counting keys on `REMOTE_ADDR` only. `HTTP_CLIENT_IP` and
`HTTP_X_FORWARDED_FOR` are client-supplied headers, so an IP-based control that
reads them can be bypassed by sending one extra header.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `mbstring`, `json`
- MySQL / MariaDB
- Apache with `mod_rewrite` (and `mod_headers` for the security headers)
- Composer is **optional** — `autoload.php` registers an equivalent PSR-4 loader when `vendor/` is absent

## Setup

```bash
# 1. Create the database and import the schema
mysql -u root -e "CREATE DATABASE temp DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root temp < backup/temp.sql

# 2. Configure — see the comments in .env.example for the quoting rules
#    and for creating a dedicated database user
cp .env.example .env

# 3. Migrate: renames menber -> member, hashes any plaintext passwords,
#    adds indexes, creates the picture table. Safe to run repeatedly.
php migrate.php

# 4. Optional — swap the fallback loader for Composer's
composer install
```

The application must be served from the document root so that `.htaccess`
rewrites reach `index.php`.

On XAMPP the `mysql` and `php` executables are usually not on `PATH`; use
`C:\xampp\mysql\bin\mysql.exe` and `C:\xampp\php\php.exe`.

## Catalog

```
┌ api                       JSON endpoints (served directly, not via the router)
│  ├─_bootstrap.php         shared setup + auth / CSRF / verb guards
│  ├─pictures.php
│  └─picture_delete.php
├─backup                    schema dump (gitignored — contains real rows)
├─log                       daily application logs (gitignored)
├─public                    static assets
│  └─images
├─src                       PSR-4: App\ => src/
│  ├─Config.php             reads .env
│  ├─Database.php           the single PDO source
│  ├─Session.php            hardened session handling
│  ├─Csrf.php
│  ├─Request.php            path / method / client IP
│  ├─Router.php
│  ├─View.php               template rendering + escaping
│  ├─Logger.php
│  ├─Repository             Member / Blocklist / Picture
│  └─Security
│     └─LoginThrottle.php   brute-force counters
├─views                     templates
├─index.php                 front controller
├─migrate.php               database migration (CLI only)
└─autoload.php              PSR-4 loader, defers to Composer when installed
```

## Database

| Table     | Purpose                                          |
| --------- | ------------------------------------------------ |
| `member`  | accounts; `pass` is a bcrypt hash, `user` UNIQUE |
| `cache`   | per-IP login failure counters                    |
| `list`    | blocked IP addresses                             |
| `picture` | picture records for the photo page and API       |

## Known limitations

- The photo page reads from the `picture` table, which ships empty — `public/images/` holds files but no rows reference them yet.
- Throttling is per-IP, so it does not slow a distributed attack spread across many addresses.
- The `secure` cookie flag only activates over HTTPS; deploying over plain HTTP transmits the session cookie in the clear.
- No automated test suite.

## Version

- v1.0 CONSTRUCT ✅
- v1.1 ROUTE ✅
- v1.2 DATABASE ✅
- v1.3 IP-CHECK ✅
- v1.4 LOG ✅
- v1.5 SESSION ✅
- v1.6 API ✅
- v2.0 REFACTOR ✅ — PSR-4 layout, `.env` configuration, CSRF, session hardening, working brute-force throttle

## License

MIT — see [LICENSE](LICENSE).