# Paraph Café — Menu Website (PHP)

A café menu website in Persian and English. The colour theme is chocolate, cream, white and black, with light and dark modes.

- **Start screen:** café logo, Start buttons for Persian or English, Instagram, phone and customer club.
- **Menu:**
  - Categories with icons, a search box (works in both languages) and a sticky category bar.
  - Shows sold-out items and "daily price" items.
  - Persian digits, right-to-left layout in Persian.
- **Customer club page:** sign in with a mobile number and SMS code, then see points and level.
- Works on mobile and desktop, and prints as a clean black-and-white menu.

> This repository contains the whole project, including the local admin panel (see `ADMIN.md`). The database is never committed.

## Online menu and online admin (GitHub Pages)

| Page | Address |
|---|---|
| Menu | **https://salehlava.github.io/paraf-cafe-menu/** |
| Online admin | **https://salehlava.github.io/paraf-cafe-menu/admin/** |

The repository [`salehlava/paraf-cafe-menu`](https://github.com/salehlava/paraf-cafe-menu) serves the online site. GitHub Pages can't run PHP, so it's a static site:

- **Menu pages:** they read `data/menu.json` (categories, items, prices) and `data/site.json` (café info, colours) in the browser.
- **Online admin:**
  - Edits those two files through the GitHub API.
  - Sign-in uses a GitHub *fine-grained personal access token* with access to that repository only and **Contents: Read and write**. The token stays in the browser.
  - After **Save**, GitHub Pages redeploys and the menu updates in about a minute.
- **Customer club and SMS:** they need a PHP server, so they only exist on the local site.

**Source and scripts:**
- `online/` holds the pages and scripts.
- `build-static.php` assembles the site (`php build-static.php <folder> [--data]`).
- `publish-menu.bat` updates the online site's pages and design. It keeps the menu data edited online.
- `replace-online-menu.bat` overwrites the online menu, café info and colours with the local admin's. It asks for confirmation.

## Railway (whole site: menu, club, admin with username + password)

1. **railway.com → New Project → Deploy from GitHub repo →** `salehlava/paraph-cafe-source`. Railway builds the `Dockerfile` by itself.
2. **Settings → Volumes:** add a volume mounted at **`/app/storage`**. It holds the database; without it, every deploy erases your menu edits.
3. **Variables:** `ADMIN_USERNAME` and `ADMIN_PASSWORD` (8+ characters). They create the admin account at the first start.
4. **Settings → Networking → Generate Domain.** The admin is at `https://<domain>/admin/`.

The site runs through `router.php`, which only serves the public pages, `/admin` and `/assets`, so the database can't be downloaded.

## Run locally

Requires PHP 7.4+ with `pdo_sqlite`.

```bash
php -S localhost:9000 router.php
```

Then open http://localhost:9000. On Windows you can double-click `start.bat` instead (edit the PHP path inside it first).

The SQLite database is created automatically in `storage/` on the first visit, filled from `data/menu.php`.

## Structure

```
index.php            Start screen + menu
club.php             Customer club
config.php           Default settings + Persian/English texts
data/menu.php        Starting menu (used once to fill the database)
lib/                 Database, security, SMS API, club logic
includes/            Header, footer, start screen, helpers
assets/              CSS, JS, favicon
router.php           Allow-list router for PHP's built-in server
online/              Online site (GitHub Pages): pages, renderer, online admin
build-static.php     Builds the online site (into public-site/, the clone of paraf-cafe-menu)
storage/             SQLite database (private, not in git)
```

## Security

- **Private files:** `router.php` only serves the public pages and `/assets`. On Apache, the `.htaccess` files block the private folders.
- **SMS codes:** rate-limited per IP, per phone number and for the whole site. Codes are hashed in the database.
- **Other:** every form has CSRF protection, output is HTML-escaped, and security headers are sent.

## Hosting

Upload to a PHP 7.4+ host and make sure `storage/` is writable.

- **Use HTTPS.**
- **Nginx:** deny `/storage`, `/lib`, `/includes` and `/data` in the server config.
