<?php
/**
 * Helper functions: language, translation, escaping, digits, sessions, CSRF, icons.
 */

function current_lang(): string
{
    if (isset($_GET['lang']) && in_array($_GET['lang'], LANGS, true)) {
        $lang = $_GET['lang'];
        setcookie('lang', $lang, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        return $lang;
    }
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], LANGS, true)) {
        return $_COOKIE['lang'];
    }
    return DEFAULT_LANG;
}

function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Link to the menu in a language. The static HTML export (build-static.php) uses .html files. */
function menu_url(string $lang): string
{
    return defined('STATIC_EXPORT') ? "menu-$lang.html" : "?lang=$lang";
}

/**
 * The customer club needs PHP + SMS, so it is hidden in the static HTML export.
 * In SMS test mode the code is shown on screen, so then the club only works on the server itself:
 * online it stays closed until a real SMS provider is set in Admin → پنل پیامک.
 */
function club_enabled(): bool
{
    if (defined('STATIC_EXPORT') || setting('club_enabled') !== '1') {
        return false;
    }
    return setting('sms_driver') !== 'log' || is_local_request();
}

/** Translate a UI string key. */
function t(string $key, string $lang): string
{
    return STRINGS[$key][$lang] ?? $key;
}

/** Site info from settings, e.g. site('name', 'fa') or site('phone'). */
function site(string $key, ?string $lang = null): string
{
    return (string) setting($lang ? "{$key}_{$lang}" : $key);
}

/** "@handle" from the Instagram link, e.g. https://instagram.com/paraph.cafe → @paraph.cafe */
function instagram_handle(): string
{
    $path = trim((string) parse_url(site('instagram'), PHP_URL_PATH), '/');
    return $path !== '' ? '@' . explode('/', $path)[0] : 'Instagram';
}

/** Convert Latin digits to Persian digits when needed. */
function num($n, string $lang): string
{
    $s = (string) $n;
    return $lang === 'fa' ? strtr($s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : $s;
}

/** Convert Persian/Arabic digits to Latin. */
function latin_digits(string $s): string
{
    return strtr($s, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

/* ---------- Colours ---------- */

/** A saved colour, or its default if missing/invalid. */
function theme_color(string $key): string
{
    $v = strtolower((string) setting($key));
    return preg_match('/^#[0-9a-f]{6}$/', $v) ? $v : SETTINGS_DEFAULTS[$key];
}

/** CSS that applies the colours chosen in the admin panel (light + dark mode). */
function theme_css(): string
{
    $p = theme_color('color_primary');
    $d = theme_color('color_dark');
    $a = theme_color('color_accent');
    $c = theme_color('color_cream');
    $l = theme_color('color_bg_light');
    $k = theme_color('color_bg_dark');

    $light = "--bg:$l;--bg-2:color-mix(in srgb,$l 90%,$p);--surface:color-mix(in srgb,$l 22%,#fff);"
        . "--choco:$p;--choco-deep:$d;--brand-dark:$d;--caramel:$a;--cream:$c;"
        . "--price-bg:$c;--price-text:$p;--line:color-mix(in srgb,$p 16%,transparent);"
        . "--toolbar-bg:color-mix(in srgb,$l 82%,transparent);--chip-bg:color-mix(in srgb,$l 22%,#fff);";
    $dark = "--bg:$k;--bg-2:color-mix(in srgb,$k 90%,$c);--surface:color-mix(in srgb,$k 80%,$p);"
        . "--choco:color-mix(in srgb,$p 85%,#fff);--choco-deep:color-mix(in srgb,$d 85%,#000);"
        . "--caramel:color-mix(in srgb,$a 85%,#fff);--price-bg:color-mix(in srgb,$k 65%,$p);"
        . "--price-text:$c;--line:color-mix(in srgb,$c 12%,transparent);"
        . "--toolbar-bg:color-mix(in srgb,$k 80%,transparent);--chip-bg:color-mix(in srgb,$k 80%,$p);";

    return ":root{{$light}}"
        . "@media (prefers-color-scheme: dark){:root:not([data-theme=\"light\"]){{$dark}}}"
        . ":root[data-theme=\"dark\"]{{$dark}}";
}

/* ---------- Sessions, CSRF, flash messages ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('paraf_sid');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    start_session();
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Form expired. Please go back, refresh the page and try again.');
    }
}

function flash(string $type, string $msg): void
{
    start_session();
    $_SESSION['flash'][] = [$type, $msg];
}

function flashes(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Icons ---------- */

const CATEGORY_ICONS = ['sandwich', 'leaf', 'cup', 'ice', 'mug', 'drip', 'smoothie', 'cake', 'glass', 'shake'];

/** Inline SVG icons (stroke uses currentColor). */
function icon(string $name): string
{
    $paths = [
        'sandwich'  => '<path d="M3 11c0-4 4-7 9-7s9 3 9 7H3z"/><path d="M3 15h18"/><path d="M4 19h16a1 1 0 0 0 1-1v-1H3v1a1 1 0 0 0 1 1z"/>',
        'leaf'      => '<path d="M5 19c0-9 6-14 15-14 0 9-5 15-14 15"/><path d="M5 19 14 10"/>',
        'cup'       => '<path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9z"/><path d="M17 11h1.5a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3c0 1.5 1 1.5 1 3M12 3c0 1.5 1 1.5 1 3"/>',
        'ice'       => '<path d="M6 4h12l-1.5 16h-9L6 4z"/><path d="M9 9h3v3H9zM12.5 13h3v3h-3z"/><path d="M14 2l-1 6"/>',
        'mug'       => '<path d="M5 8h11v9a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V8z"/><path d="M16 10h2a2 2 0 0 1 0 4h-2"/><path d="M7 4c1 .8 1 1.6 0 2.4M10.5 4c1 .8 1 1.6 0 2.4M14 4c1 .8 1 1.6 0 2.4"/>',
        'drip'      => '<path d="M5 4h14l-5 7h-4L5 4z"/><path d="M12 11v3"/><path d="M7 15h10v3a3 3 0 0 1-3 3h-4a3 3 0 0 1-3-3v-3z"/>',
        'smoothie'  => '<path d="M6 8h12l-1.5 13h-9L6 8z"/><path d="M5 8h14"/><path d="M13 8l3-6"/><circle cx="10" cy="5" r="2"/>',
        'cake'      => '<path d="M4 12h16v8H4z"/><path d="M4 16c2 1.5 4 1.5 6 0s4-1.5 6 0 3 1 4 0"/><path d="M12 12V8"/><path d="M12 4.5c.8.8.8 1.8 0 2.5-.8-.7-.8-1.7 0-2.5z"/>',
        'glass'     => '<path d="M5 4h14l-7 8-7-8z"/><path d="M12 12v8M8 20h8"/><circle cx="16.5" cy="5.5" r="2"/>',
        'shake'     => '<path d="M7 9h10l-1.3 12H8.3L7 9z"/><path d="M6 9a6 4 0 0 1 12 0"/><path d="M12 5V2l3-1"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/>',
        'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
        'pin'       => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'gift'      => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8h14v-8M12 8v12"/><path d="M12 8S10.5 3 8 3.5 7 8 12 8zM12 8s1.5-5 4-4.5S17 8 12 8z"/>',
    ];
    $p = $paths[$name] ?? $paths['cup'];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
