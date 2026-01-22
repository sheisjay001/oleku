# InfinityFree Deployment Guide — Oleku (PHP + MySQL)

## Overview
Deploy the Oleku EdTech platform to InfinityFree reliably. This guide covers file upload, database setup, live configuration, testing, and common fixes specific to InfinityFree.

## Requirements
- PHP: 8.x
- MySQL: provided by InfinityFree
- Domain: free subdomain or custom domain attached in control panel
- No shell/Composer on server; upload ready-to-run code

## Prepare Locally
- Confirm the app runs in XAMPP with the default config (`includes/config.php`: `DB_HOST=localhost`, `DB_USER=root`, `DB_PASS=''`, `DB_NAME=oleku_db`).
- Ensure `uploads/` and `cache/` are writable locally if used.
- Zip the `oleku` folder excluding local-only files (`/vendor` if unused, logs, `.env.local`).

## Create Hosting Resources
- Log in to InfinityFree client area → Control Panel.
- Add a domain/subdomain and note the `Document Root` (usually `htdocs`).
- Create a MySQL database:
  - Host: `sqlNNN.infinityfree.com` (exact host appears in the DB page)
  - Database name: `epiz_XXXXXXXX_oleku`
  - Username: `epiz_XXXXXXXX`
  - Password: generated in panel

## Upload Files
- Open File Manager → navigate to `htdocs`.
- Upload and extract the zipped `oleku` contents so that `index.php` sits directly under your `htdocs` (or adjust paths if using a subfolder).
- Keep `.htaccess` in place; see notes below if you hit 500 errors.

## Configure For Production
- Edit `includes/config.php` to match InfinityFree credentials:
  - `DB_HOST` → `sqlNNN.infinityfree.com`
  - `DB_USER` → `epiz_XXXXXXXX`
  - `DB_PASS` → your DB password
  - `DB_NAME` → `epiz_XXXXXXXX_oleku`
  - `SITE_URL` → your live domain, e.g., `https://example.epizy.com`
- Turn off display errors in production:
  - `error_reporting(E_ALL);` keep for logging
  - `ini_set('display_errors', 0);`
- Store any API keys in a non-public PHP file (e.g., `config/secrets.php`) and include it; InfinityFree free plan does not support secure environment variables.

## Database Import
- If you have a local schema/data:
  - Export from phpMyAdmin (XAMPP) as SQL.
  - Import via phpMyAdmin on InfinityFree to your `epiz_...` database.
- Verify connectivity using `test_db.php` (already included). Open `/test_db.php` in the browser to confirm PDO connectivity and run a sample query.

## .htaccess Notes
- Current rules include URL rewriting, security headers, gzip, and cache headers.
- If you see a `500` error immediately after upload:
  - Comment out `php_value`/`php_flag` directives; some free hosts restrict these.
  - Keep `RewriteEngine On` and the front-controller rule:
    - `RewriteCond %{REQUEST_FILENAME} !-f`
    - `RewriteCond %{REQUEST_FILENAME} !-d`
    - `RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]`

## Frontend Assets
- Tailwind CDN is used in `index.php` and other pages; this is fine for InfinityFree.
- If assets are not loading, confirm paths like `assets/css/style.css` and `assets/js/main.js` exist and are correctly referenced relative to your document root.

## AI/OCR Integration
- Run AI/OCR via external APIs only; no local heavy processing on InfinityFree.
- Use `curl`/`openssl` (available) to call external services.
- Keep uploads ≤ 1.5MB to fit free-plan limits and avoid timeouts.

## Common Issues & Fixes
- Database connection failed:
  - Check `DB_HOST` is not `localhost` on InfinityFree; use `sqlNNN.infinityfree.com`.
  - Confirm the correct `epiz_...` database name and user.
- 403/500 errors on pages:
  - Review `.htaccess` directives as above; remove disallowed `php_value` lines.
  - Ensure files are under the correct `htdocs` path.
- Parse error in a PHP include:
  - Check for missing `?>`, unmatched braces, stray HTML inside PHP blocks.
  - The reported example: `includes/header.php` line 417 — ensure the `if/elseif/else/endif` blocks are closed properly before the template ends.
- Sessions not persisting:
  - Verify `session_start()` runs once (see `includes/config.php`).
  - Avoid output before headers in included files.

## Post-Deployment Checklist
- Visit `/test_db.php` to confirm PDO connectivity.
- Open `index.php` and navigate major flows:
  - Auth (`auth/login.php`, `auth/register.php`)
  - JAMB subjects (`jamb-subjects.php`)
  - Dashboard (`dashboard/index.php`)
- Verify SSL (Control Panel → Free SSL) and enforce `https` in `SITE_URL`.
- Check error logs if enabled and ensure `display_errors` is off.

## When To Migrate
- Move to a VPS/cloud when you need server-side AI processing, background jobs, or higher upload limits. Keep InfinityFree for early demos and static workloads.

## License
Proprietary — controlled deployment only.
