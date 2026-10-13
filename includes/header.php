<?php
/** @var string $lang */
$dir = $lang === 'fa' ? 'rtl' : 'ltr';
$pageTitle = $pageTitle ?? t('menu', $lang);
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e(site('name', $lang)) ?> — <?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(site('tagline', $lang)) ?>">
    <meta name="theme-color" content="<?= theme_color('color_dark') ?>">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <style><?= theme_css() ?></style>

    <script>
        // Apply saved theme before paint to avoid a flash.
        document.documentElement.className += ' js';
        try {
            var th = localStorage.getItem('theme');
            if (th === 'dark' || th === 'light') document.documentElement.setAttribute('data-theme', th);
        } catch (e) {}
    </script>
</head>
<body>

<header class="topbar">
    <div class="container topbar__inner">
        <a class="brand" href="./">
            <span class="brand__mark" aria-hidden="true">P</span>
            <span class="brand__name"><?= e(site('name', $lang)) ?></span>
        </a>

        <div class="topbar__actions">
            <?php if (setting('club_enabled') === '1'): ?>
                <a class="club-link" href="club.php?lang=<?= e($lang) ?>" title="<?= e(t('club', $lang)) ?>">
                    <?= icon('gift') ?>
                    <span><?= e(t('club', $lang)) ?></span>
                </a>
            <?php endif; ?>
            <nav class="lang-switch" aria-label="Language">
                <a href="?lang=fa" class="<?= $lang === 'fa' ? 'is-active' : '' ?>" hreflang="fa" lang="fa">فا</a>
                <a href="?lang=en" class="<?= $lang === 'en' ? 'is-active' : '' ?>" hreflang="en" lang="en">EN</a>
            </nav>
            <button class="icon-btn" id="themeToggle" type="button" aria-label="<?= e(t('theme', $lang)) ?>" title="<?= e(t('theme', $lang)) ?>">
                <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </button>
        </div>
    </div>
</header>
