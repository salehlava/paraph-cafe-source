<?php
/**
 * Admin page shell: admin_header('Title', 'nav-key') … admin_footer().
 */

const ADMIN_NAV = [
    'dashboard' => ['index.php',    'داشبورد',          '<path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/>'],
    'menu'      => ['menu.php',     'مدیریت منو',       '<path d="M4 6h16M4 12h16M4 18h10"/>'],
    'members'   => ['members.php',  'باشگاه مشتریان',   '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M17 11a3 3 0 1 0 0-6M22 21a6 6 0 0 0-4-5.7"/>'],
    'sms'       => ['sms.php',      'پنل پیامک',         '<path d="M4 5h16v11H8l-4 4z"/><path d="M8 10h8M8 13h5"/>'],
    'settings'  => ['settings.php', 'تنظیمات',          '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>'],
    'appearance'=> ['appearance.php', 'ظاهر و رنگ‌ها',  '<circle cx="13.5" cy="6.5" r="1.5"/><circle cx="17.5" cy="10.5" r="1.5"/><circle cx="8.5" cy="7.5" r="1.5"/><circle cx="6.5" cy="12.5" r="1.5"/><path d="M12 2a10 10 0 0 0 0 20c1.1 0 2-.9 2-2 0-.5-.2-1-.5-1.3-.3-.4-.5-.8-.5-1.3 0-1.1.9-2 2-2h2.4A5.6 5.6 0 0 0 22 9.8C22 5.5 17.5 2 12 2z"/>'],
    'account'   => ['account.php',  'حساب کاربری',      '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>'],
];

function admin_svg(string $path, string $cls = 'icon'): string
{
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function admin_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — پنل مدیریت <?= e(site('name', 'fa')) ?></title>
    <link rel="icon" href="../assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=4">
    <script>
        try { var th = localStorage.getItem('theme'); if (th) document.documentElement.setAttribute('data-theme', th); } catch (e) {}
    </script>
</head>
    <?php
}

function admin_header(string $title, string $active): void
{
    admin_head($title);
    ?>
<body class="admin">
<aside class="sidebar" id="sidebar">
    <div class="sidebar__brand">
        <span class="mark">P</span>
        <div>
            <strong><?= e(site('name', 'fa')) ?></strong>
            <small>پنل مدیریت</small>
        </div>
    </div>
    <nav class="sidebar__nav">
        <?php foreach (ADMIN_NAV as $key => [$href, $label, $path]): ?>
            <a href="<?= $href ?>" class="<?= $key === $active ? 'is-active' : '' ?>"><?= admin_svg($path) ?><span><?= e($label) ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar__foot">
        <a href="../" target="_blank"><?= admin_svg('<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>') ?><span>مشاهده سایت</span></a>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit"><?= admin_svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>') ?><span>خروج (<?= e($_SESSION['admin_name'] ?? '') ?>)</span></button>
        </form>
    </div>
</aside>
<div class="backdrop" id="backdrop"></div>

<div class="main">
    <header class="main__top">
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="منو"><?= admin_svg('<path d="M4 6h16M4 12h16M4 18h16"/>') ?></button>
        <h1><?= e($title) ?></h1>
    </header>
    <div class="main__body">
        <?php foreach (flashes() as [$type, $msg]): ?>
            <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
        <?php endforeach; ?>
    <?php
}

function admin_footer(): void
{
    ?>
    </div>
</div>
<script>
    (function () {
        var sb = document.getElementById('sidebar'), bd = document.getElementById('backdrop');
        function toggle(open) { document.body.classList.toggle('nav-open', open); }
        document.getElementById('menuToggle').addEventListener('click', function () { toggle(!document.body.classList.contains('nav-open')); });
        bd.addEventListener('click', function () { toggle(false); });
        // Confirm dangerous actions
        document.addEventListener('click', function (e) {
            var b = e.target.closest('[data-confirm]');
            if (b && !confirm(b.getAttribute('data-confirm'))) e.preventDefault();
        });
    })();
</script>
</body>
</html>
    <?php
}
