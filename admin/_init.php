<?php
/**
 * Shared setup for every admin page. Redirects to login unless ADMIN_PUBLIC is defined.
 */
require dirname(__DIR__) . '/lib/bootstrap.php';
require __DIR__ . '/_layout.php';

start_session();

const ADMIN_IDLE_TIMEOUT = 7200; // seconds

/** Fingerprint of the admin's password: changes whenever the password (or username) changes. */
function admin_fingerprint(array $admin): string
{
    return hash_hmac('sha256', $admin['id'] . '|' . $admin['username'] . '|' . $admin['password_hash'], (string) setting('app_secret'));
}

function admin_sign_in(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id']   = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['username'];
    $_SESSION['admin_fp']   = admin_fingerprint($admin);
    $_SESSION['admin_seen'] = time();
}

function admin_sign_out(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_fp'], $_SESSION['admin_seen']);
}

function admin_logged_in(): bool
{
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    if (time() - ($_SESSION['admin_seen'] ?? 0) > ADMIN_IDLE_TIMEOUT) {
        admin_sign_out();
        return false;
    }
    // Log out if the account was deleted or its password/username changed elsewhere (e.g. reset-admin.bat).
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin || !hash_equals(admin_fingerprint($admin), (string) ($_SESSION['admin_fp'] ?? ''))) {
        admin_sign_out();
        return false;
    }
    $_SESSION['admin_seen'] = time();
    return true;
}

// Absolute URL of the admin folder (e.g. "/admin/"), so redirects work from "/admin" too.
define('ADMIN_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/') . '/');

if (!defined('ADMIN_PUBLIC') && !admin_logged_in()) {
    redirect(ADMIN_URL . 'login.php');
}

/** Read a POST field as a trimmed string. */
function post(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

/** Parse a price field: Persian digits allowed, empty = null (daily price). */
function parse_price(string $s): ?int
{
    $s = preg_replace('/\D+/', '', latin_digits($s));
    return $s === '' ? null : (int) $s;
}

/** Persian-digit date "YYYY-MM-DD HH:MM" from a stored timestamp. */
function fa_date(string $ts, bool $time = false): string
{
    return num(substr($ts, 0, $time ? 16 : 10), 'fa');
}
