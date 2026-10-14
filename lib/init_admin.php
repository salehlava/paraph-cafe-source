<?php
/**
 * Command-line: create the database and, if there is no admin yet, the single admin account.
 * Used when the site starts in Docker (docker/entrypoint.sh), where the browser setup form
 * is not available (it only works on the server computer itself).
 *
 *   ADMIN_USERNAME  (optional, default "admin")
 *   ADMIN_PASSWORD  (optional, min. 8 characters) — if missing, a random one is printed to the log
 *
 * Does nothing when an admin already exists, so it is safe to run on every start.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/bootstrap.php';

$pdo = db();   // creates / upgrades the database

if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
    echo "[paraph] Admin account exists.\n";
    exit;
}
if (!is_dir(ROOT . '/admin')) {
    echo "[paraph] No admin panel in this build — no admin account created.\n";
    exit;
}

$username = getenv('ADMIN_USERNAME') ?: 'admin';
$password = (string) getenv('ADMIN_PASSWORD');
if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
    $username = 'admin';
}
$generated = strlen($password) < 8;
if ($generated) {
    $password = 'Paraph-' . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 8);
}

$pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
    ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);

echo "[paraph] Admin account created. Username: $username\n";
echo $generated
    ? "[paraph] Password: $password   ← change it in the admin panel (حساب کاربری)\n"
    : "[paraph] Password: (from ADMIN_PASSWORD)\n";
