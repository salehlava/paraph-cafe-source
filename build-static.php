<?php
/**
 * Builds a static HTML copy of the menu into docs/ for GitHub Pages.
 * GitHub Pages can't run PHP, so each page is rendered once to plain HTML.
 * No admin panel and no customer club (they need a PHP server).
 *
 *   php build-static.php          → writes docs/
 *
 * The menu, prices, café info and colours come from your local database,
 * so changes made in the admin panel appear after the next build.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Child mode: render one page to stdout.
if (($argv[1] ?? '') === '--render') {
    define('STATIC_EXPORT', true);
    $lang = $argv[2] ?? '';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $lang !== '' ? ['lang' => $lang] : [];
    require __DIR__ . '/index.php';
    exit;
}

$out = __DIR__ . '/docs';
$pages = [
    'index.html'   => '',     // start screen
    'menu-fa.html' => 'fa',
    'menu-en.html' => 'en',
];
$assets = ['assets/css/style.css', 'assets/js/main.js', 'assets/img/favicon.svg'];

// Start from a clean folder so removed files don't linger.
if (is_dir($out)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($out, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
}
@mkdir($out, 0775, true);

foreach ($pages as $file => $lang) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --render ' . escapeshellarg($lang);
    $html = shell_exec($cmd);
    if (!is_string($html) || stripos($html, '</html>') === false) {
        fwrite(STDERR, "Failed to render $file\n");
        exit(1);
    }
    file_put_contents("$out/$file", $html);
    echo "  wrote docs/$file\n";
}

foreach ($assets as $a) {
    @mkdir(dirname("$out/$a"), 0775, true);
    copy(__DIR__ . "/$a", "$out/$a");
    echo "  copied docs/$a\n";
}

file_put_contents("$out/.nojekyll", '');   // serve files as-is on GitHub Pages

echo "Done.\n";
