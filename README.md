# Paraph Café — Menu Website (PHP)

A café menu website in Persian and English. The colour theme is chocolate, cream, white and black, with light and dark modes.

- **Start screen:** café logo, Start buttons for Persian or English, Instagram, phone and customer club.
- **Menu:**
  - Categories with icons, a search box (works in both languages) and a sticky category bar.
  - Shows sold-out items and "daily price" items.
  - Persian digits, right-to-left layout in Persian.
- **Customer club page:** sign in with a mobile number and SMS code, then see points and level.
- Works on mobile and desktop, and prints as a clean black-and-white menu.

> The admin panel (menu editing, members, SMS panel, colours) is kept private and is not part of this repository.

## Online menu

**https://salehlava.github.io/paraf-cafe-menu/**

GitHub Pages can't run PHP, so the online menu is a static HTML copy in `docs/`, made by `build-static.php`. It has the start screen and the menu in both languages. The customer club needs a PHP server, so it isn't part of the online copy.

**Two repositories:**

| Repository | Visibility | Contents |
|---|---|---|
| `salehlava/paraph-cafe-source` (this one) | **private** | full PHP source |
| `salehlava/paraf-cafe-menu` | public | **only** the HTML from `docs/`, served by GitHub Pages |

To update the online menu after changing it locally, run `publish-menu.bat`, or:

```bash
php build-static.php
git add docs && git commit -m "Update online menu"
git push origin main                                  # private source
git subtree split --prefix docs -b public-menu
git push public public-menu:main                      # public HTML menu
```

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
build-static.php     Builds the static online menu into docs/
docs/                Static online menu (GitHub Pages)
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
