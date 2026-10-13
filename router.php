<?php
/**
 * Router for PHP's built-in server (used by start.bat).
 *
 * Allow-list: only the public pages, the admin pages and the files in /assets
 * can be requested. Everything else (database, code, config, .bat files…) is refused.
 * On Apache the .htaccess files do the same job.
 */
// Split off the query string by hand: parse_url() treats "//x" as a host name.
$uri   = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$qpos  = strpos($uri, '?');
$path  = rawurldecode($qpos === false ? $uri : substr($uri, 0, $qpos));
$query = $qpos === false ? '' : substr($uri, $qpos + 1);
if ($path === '' || $path[0] !== '/') {
    $path = '/' . $path;
}

/** Normalise: backslashes → slashes, remove empty, "." and ".." segments. */
$segments = [];
foreach (explode('/', str_replace('\\', '/', $path)) as $seg) {
    if ($seg === '' || $seg === '.') {
        continue;
    }
    if ($seg === '..') {
        array_pop($segments);
        continue;
    }
    $segments[] = $seg;
}
$clean = '/' . implode('/', $segments) . (substr($path, -1) === '/' && $segments ? '/' : '');

function deny(int $code = 404): void
{
    http_response_code($code);
    exit($code === 404 ? 'Not Found' : 'Forbidden');
}

// Only canonical paths: "//x", "/a/../b", "\" etc. are never sent by real browsers.
if ($clean !== $path) {
    deny();
}

$hasAdmin = is_dir(__DIR__ . '/admin');   // the admin panel is optional (not in the public repository)

// /admin → /admin/ so relative links work.
if ($clean === '/admin' && $hasAdmin) {
    header('Location: /admin/' . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

$allowed =
    $clean === '/'
    || in_array($clean, ['/index.php', '/club.php'], true)
    || ($hasAdmin && ($clean === '/admin/' || preg_match('#^/admin/[a-z][a-z0-9-]*\.php$#', $clean)))   // admin pages (not _init / _layout)
    || preg_match('#^/assets/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_.-]+\.(css|js|svg|png|jpe?g|webp|gif|ico|woff2?)$#i', $clean);

if (!$allowed) {
    deny();
}

return false; // let the built-in server serve the allowed file
