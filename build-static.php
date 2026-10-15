<?php
/**
 * Builds the online site (GitHub Pages): start screen, menu pages and the online admin.
 *
 *   php build-static.php [target-folder] [--data]
 *
 * target-folder  defaults to docs/
 * --data         also export the menu, café info and colours from the LOCAL database into
 *                data/menu.json + data/site.json. Without it, existing data files are kept,
 *                because the online admin edits them (only missing files are created).
 *
 * The pages read data/*.json in the browser, so menu changes don't need a rebuild.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/lib/bootstrap.php';

$args     = array_slice($argv, 1);
$withData = in_array('--data', $args, true);
$target   = array_values(array_filter($args, fn($a) => $a !== '--data'))[0] ?? (__DIR__ . '/docs');
$out      = rtrim(str_replace('\\', '/', $target), '/');

@mkdir($out, 0775, true);

/** Copy one file, creating folders as needed. */
function put(string $from, string $to): void
{
    @mkdir(dirname($to), 0775, true);
    copy($from, $to) || exit("Could not copy $from\n");
    echo '  ' . $to . "\n";
}

// 1) Pages and scripts of the online site
$src = __DIR__ . '/online';
$it  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($src) + 1));
    put($f->getPathname(), "$out/$rel");
}

// 2) Shared styles, menu behaviour and logo from the PHP site
foreach (['assets/css/style.css', 'assets/css/admin.css', 'assets/js/main.js', 'assets/img/favicon.svg'] as $a) {
    put(__DIR__ . "/$a", "$out/$a");
}
file_put_contents("$out/.nojekyll", '');   // serve files as-is on GitHub Pages

// 3) Data files (menu + café info)
@mkdir("$out/data", 0775, true);
$json = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

if ($withData || !is_file("$out/data/menu.json")) {
    $menu = ['categories' => []];
    foreach (menu_load(true) as $c) {
        $menu['categories'][] = [
            'id'      => 'c' . $c['id'],
            'icon'    => $c['icon'],
            'title'   => ['fa' => $c['title_fa'], 'en' => $c['title_en']],
            'note'    => ['fa' => $c['note_fa'], 'en' => $c['note_en']],
            'visible' => (bool) $c['visible'],
            'items'   => array_map(fn($i) => [
                'id'        => 'i' . $i['id'],
                'name'      => ['fa' => $i['name_fa'], 'en' => $i['name_en']],
                'price'     => $i['price'] === null ? null : (int) $i['price'],
                'available' => (bool) $i['available'],
                'addon'     => (bool) $i['is_addon'],
            ], $c['items']),
        ];
    }
    file_put_contents("$out/data/menu.json", json_encode($menu, $json) . "\n");
    echo "  $out/data/menu.json  (from local database)\n";
} else {
    echo "  $out/data/menu.json  kept (edited online)\n";
}

if ($withData || !is_file("$out/data/site.json")) {
    $site = [];
    foreach (['name', 'tagline', 'hours', 'address'] as $k) {
        $site[$k] = ['fa' => site($k, 'fa'), 'en' => site($k, 'en')];
    }
    $site['phone']     = site('phone');
    $site['instagram'] = site('instagram');
    $site['colors']    = [];
    foreach (COLOR_KEYS as $k) {
        $site['colors'][substr($k, 6)] = theme_color($k);   // color_primary → primary
    }
    file_put_contents("$out/data/site.json", json_encode($site, $json) . "\n");
    echo "  $out/data/site.json  (from local database)\n";
} else {
    echo "  $out/data/site.json  kept (edited online)\n";
}

echo "Done.\n";
