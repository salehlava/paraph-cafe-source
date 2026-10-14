# Paraph Café — Menu, Customer Club & Admin Panel (PHP)

A café menu website in Persian and English, with a customer club, an SMS panel and an admin panel. The colour theme is chocolate, cream, white and black, with light and dark modes.

## Run

Double-click **`start.bat`**. It opens http://localhost:9000.

| Page | URL |
|---|---|
| Start screen | http://localhost:9000 |
| Menu | http://localhost:9000/?lang=fa (or `?lang=en`) |
| Customer club | http://localhost:9000/club.php |
| Admin panel | http://localhost:9000/admin |

**First visit to `/admin`:** it asks you to create the admin username and password.

The database (SQLite) is created automatically at `storage/paraf.sqlite`. Back up this file: it holds the menu, members and settings.

## Admin panel

- **داشبورد (Dashboard):** member and SMS counts, new members.
- **مدیریت منو (Menu):** add, edit and delete categories and items, and change prices. You can also reorder them (▲▼), hide a category, and mark an item "sold out" (ناموجود).
- **باشگاه مشتریان (Customer club):** search members, add or subtract points, edit or delete members, and send an SMS to one member. You can also export all members to Excel (CSV).
- **پنل پیامک (SMS panel):** connect an SMS provider, send a test SMS, send bulk SMS (to all members, one level, or a custom list of numbers), and view the log.
- **تنظیمات (Settings):** café name, address, phone, hours and Instagram. Also club on/off, the points needed for each level, and the admin password.

## Customer club

1. The customer enters their mobile number and receives a 5-digit code by SMS.
2. A new customer enters their name and (optionally) birthday, then gets a welcome SMS.
3. The customer sees their card with points, level (Bronze → Silver → Gold) and progress.

Staff add points from the admin panel (e.g. after each purchase).

Security:
- Codes expire after 5 minutes and allow at most 5 attempts.
- A new code can be requested once every 60 seconds.
- Each session can request at most 5 codes per 15 minutes.

## SMS providers

Choose the provider in Admin → پنل پیامک:

| Provider | Needs |
|---|---|
| Test mode (`log`) | nothing. No SMS is sent, the code is shown on screen. |
| Kavenegar | API key + sender line |
| SMS.ir | API key + line number |
| Melipayamak | panel username + password + sender line |
| IPPanel / Faraz SMS | API key + sender line |

From code: `sms_send('09121234567', 'متن پیام');` or `sms_send([$phone1, $phone2], $text);` (in `lib/sms.php`).

Use **Send test SMS** after entering your keys. The provider's reply is shown in the log, which helps if something is wrong.

## Structure

```
index.php            Start screen + menu
club.php             Customer club
admin/               Admin panel (login, dashboard, menu, members, sms, settings)
lib/                 bootstrap, database, SMS API, club logic
includes/            header, footer, welcome screen, helpers
config.php           Default settings + Persian/English texts
data/menu.php        Starting menu (used only once, to fill the database)
storage/             SQLite database (private)
assets/              CSS, JS, favicon
router.php           Blocks private folders when using start.bat
```

Private folders (`storage`, `lib`, `includes`, `data`) are blocked:
- by `router.php` with `start.bat`;
- by `.htaccess` on Apache hosting.

## Security

- **Private files:** `router.php` (with `start.bat`) only serves the public pages, the admin pages and `/assets`. On Apache, the `.htaccess` files do the same.
- **Admin login:**
  - Locked for 15 minutes after 5 wrong passwords from the same IP (stored in the database, so clearing cookies doesn't help).
  - Signed out after 2 hours idle.
  - A password change or `reset-admin.bat` signs out every other browser.
- **First admin:** can only be created from the server computer itself.
- **SMS codes:**
  - Limits: 5 per hour per IP, 5 per day per phone number, 200 per hour for the whole site (protects your SMS credit).
  - Codes are hashed in the database and hidden in the SMS log.
- **Other:**
  - Every form has CSRF protection.
  - Output is HTML-escaped.
  - The site can't be embedded in other sites.
  - CSV exports neutralise spreadsheet formulas.
  - PHP errors go to `storage/php-errors.log`, never to the visitor.

## Hosting

Upload everything to a PHP 7.4+ host with `pdo_sqlite` (and `curl` or `openssl` for SMS). Make sure the `storage/` folder is writable.

- **Use HTTPS.** Secure cookies and HSTS turn on automatically.
- **Apache:** the included `.htaccess` files protect the private folders.
- **Nginx:** `.htaccess` is ignored. Deny `/storage`, `/lib`, `/includes`, `/data`, `/admin/_*` and `*.sqlite` in the server config, or the database can be downloaded.
- **Backups:** back up `storage/paraf.sqlite` regularly.
