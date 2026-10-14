<?php
/**
 * Command-line tool: reset the admin username/password (run reset-admin.bat).
 * The site has only one admin: this updates it (or creates it if none exists).
 * Not reachable from the web.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
require __DIR__ . '/bootstrap.php';

$username = $argv[1] ?? 'admin';
if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
    exit("Invalid username. Use 3-32 letters, numbers or . _ -\n");
}
$password = 'Paraph-' . substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 8);
$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo = db();
$first = $pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
if ($first) {
    // Only one admin is allowed: keep the first account and update it.
    $pdo->prepare('DELETE FROM admins WHERE id != ?')->execute([$first]);
    $pdo->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?')->execute([$username, $hash, $first]);
} else {
    $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$username, $hash]);
}

echo "\n  Admin login has been reset.\n\n";
echo "  Username: $username\n";
echo "  Password: $password\n\n";
echo "  Sign in at http://localhost:9000/admin/ and change it in: حساب کاربری\n\n";
