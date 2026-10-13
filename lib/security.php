<?php
/**
 * Security helpers: error handling, HTTP headers, session hardening, rate limiting.
 * Loaded by lib/bootstrap.php on every request.
 */

/* ---------- Errors: never show internals to visitors, log them privately ---------- */
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT . '/storage/php-errors.log');
}

/* ---------- Session hardening (applies when start_session() runs) ---------- */
ini_set('session.use_strict_mode', '1');      // reject session IDs the server didn't create
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
if (is_https()) {
    ini_set('session.cookie_secure', '1');
}

/* ---------- Security headers ---------- */
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: DENY');                          // no embedding in other sites (clickjacking)
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

/** Visitor IP. X-Forwarded-For is NOT trusted (it can be faked). */
function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/* ---------- Rate limiting (stored in the database, so clearing cookies doesn't help) ---------- */

/** How many times $key was recorded in the last $window seconds. */
function rate_count(string $key, int $window): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM rate_hits WHERE key = ? AND ts > ?');
    $stmt->execute([$key, time() - $window]);
    return (int) $stmt->fetchColumn();
}

/** Record one hit for each key. */
function rate_hit(string ...$keys): void
{
    $stmt = db()->prepare('INSERT INTO rate_hits (key, ts) VALUES (?, ?)');
    foreach ($keys as $key) {
        $stmt->execute([$key, time()]);
    }
    if (random_int(1, 50) === 1) {                            // occasional clean-up
        db()->exec('DELETE FROM rate_hits WHERE ts < ' . (time() - 86400 * 2));
    }
}

/**
 * Check several limits at once: [[key, max, windowSeconds], ...].
 * Returns seconds to wait (0 = allowed).
 */
function rate_check(array $limits): int
{
    $wait = 0;
    foreach ($limits as [$key, $max, $window]) {
        if (rate_count($key, $window) >= $max) {
            $stmt = db()->prepare('SELECT MIN(ts) FROM (SELECT ts FROM rate_hits WHERE key = ? AND ts > ? ORDER BY ts DESC LIMIT ?)');
            $stmt->execute([$key, time() - $window, $max]);
            $wait = max($wait, (int) $stmt->fetchColumn() + $window - time(), 1);
        }
    }
    return $wait;
}

function rate_clear(string $key): void
{
    db()->prepare('DELETE FROM rate_hits WHERE key = ?')->execute([$key]);
}

/* ---------- CSV export: stop spreadsheet formulas (=, +, -, @) in user data ---------- */
function csv_safe($value): string
{
    $s = (string) $value;
    return ($s !== '' && strpos("=+-@\t\r", $s[0]) !== false) ? "'" . $s : $s;
}
