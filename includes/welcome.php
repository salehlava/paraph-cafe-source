<?php
/** Start screen shown at the site root, before the menu. */
$phone = site('phone');
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e(site('name', 'fa')) ?> · <?= e(site('name', 'en')) ?></title>
    <meta name="theme-color" content="<?= theme_color('color_dark') ?>">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&family=Playfair+Display:ital,wght@0,700;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=4">
    <style><?= theme_css() ?></style>
</head>
<body class="welcome-body">

<main class="welcome">
    <div class="welcome__glow" aria-hidden="true"></div>

    <div class="welcome__card">
        <span class="welcome__logo" aria-hidden="true">P</span>

        <h1 class="welcome__title">
            <span lang="fa"><?= e(site('name', 'fa')) ?></span>
            <span lang="en"><?= e(site('name', 'en')) ?></span>
        </h1>

        <p class="welcome__tagline">
            <span lang="fa"><?= e(site('tagline', 'fa')) ?></span>
            <span lang="en"><?= e(site('tagline', 'en')) ?></span>
        </p>

        <div class="welcome__actions">
            <a class="start-btn start-btn--solid" href="?lang=fa" lang="fa">
                <span>شروع · مشاهده منو</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 5l-7 7 7 7"/></svg>
            </a>
            <a class="start-btn start-btn--outline" href="?lang=en" lang="en" dir="ltr">
                <span>Start · View Menu</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
        </div>

        <?php if (setting('club_enabled') === '1'): ?>
            <a class="welcome__club" href="club.php?lang=fa">
                <?= icon('gift') ?>
                <span>
                    <strong>باشگاه مشتریان</strong>
                    <small lang="en">Customer Club</small>
                </span>
            </a>
        <?php endif; ?>

        <div class="welcome__social">
            <?php if (site('instagram') !== ''): ?>
                <a class="social-btn" href="<?= e(site('instagram')) ?>" target="_blank" rel="noopener">
                    <?= icon('instagram') ?>
                    <span>اینستاگرام · Instagram</span>
                </a>
            <?php endif; ?>
            <?php if ($phone !== ''): ?>
                <a class="social-btn" href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>">
                    <?= icon('phone') ?>
                    <span dir="ltr"><?= e($phone) ?></span>
                </a>
            <?php endif; ?>
        </div>

        <p class="welcome__hours">
            <span lang="fa"><?= e(site('hours', 'fa')) ?></span>
            <span class="dot" aria-hidden="true">•</span>
            <span lang="en"><?= e(site('hours', 'en')) ?></span>
        </p>
        <?php if (site('address', 'fa') !== ''): ?>
            <p class="welcome__address"><?= icon('pin') ?> <?= e(site('address', 'fa')) ?></p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
