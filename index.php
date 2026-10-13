<?php
require __DIR__ . '/lib/bootstrap.php';

// No language chosen in the URL → show the start screen.
if (!isset($_GET['lang']) || !in_array($_GET['lang'], LANGS, true)) {
    require __DIR__ . '/includes/welcome.php';
    exit;
}

$lang  = current_lang();
$other = $lang === 'fa' ? 'en' : 'fa';
$menu  = menu_load();

require __DIR__ . '/includes/header.php';
?>

<main>
    <!-- Hero -->
    <section class="hero">
        <div class="container hero__inner">
            <p class="hero__eyebrow"><?= e(t('menu', $lang)) ?> · <?= $lang === 'fa' ? 'Menu' : 'منو' ?></p>
            <h1 class="hero__title"><?= e(site('name', $lang)) ?></h1>
            <p class="hero__tagline"><?= e(site('tagline', $lang)) ?></p>
            <div class="hero__meta">
                <span class="pill"><?= e(site('hours', $lang)) ?></span>
            </div>
        </div>
        <svg class="hero__drip" viewBox="0 0 1200 60" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,0 H1200 V18 C1170,18 1165,46 1140,46 C1115,46 1112,18 1080,18 H960 C935,18 935,54 905,54 C875,54 878,18 850,18 H700 C680,18 680,38 660,38 C640,38 640,18 620,18 H460 C435,18 432,58 400,58 C368,58 368,18 340,18 H220 C200,18 198,40 175,40 C152,40 150,18 130,18 H0 Z"/>
        </svg>
    </section>

    <!-- Sticky toolbar: search + categories -->
    <div class="toolbar" id="toolbar">
        <div class="container">
            <label class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" id="search" placeholder="<?= e(t('search', $lang)) ?>" autocomplete="off">
            </label>
            <nav class="chips" id="chips" aria-label="<?= e(t('menu', $lang)) ?>">
                <?php foreach ($menu as $cat): ?>
                    <a class="chip" href="#cat-<?= (int) $cat['id'] ?>" data-target="cat-<?= (int) $cat['id'] ?>">
                        <?= icon($cat['icon']) ?>
                        <span><?= e($cat['title_' . $lang]) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <!-- Menu -->
    <div class="container">
        <div class="menu" id="menu">
            <?php foreach ($menu as $i => $cat): ?>
                <section class="category" id="cat-<?= (int) $cat['id'] ?>">
                    <header class="category__head">
                        <span class="category__icon"><?= icon($cat['icon']) ?></span>
                        <div class="category__titles">
                            <h2><?= e($cat['title_' . $lang]) ?></h2>
                            <span class="category__sub" lang="<?= $other ?>"><?= e($cat['title_' . $other]) ?></span>
                        </div>
                        <span class="category__num"><?= num(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), $lang) ?></span>
                    </header>

                    <?php if ($cat['note_' . $lang] !== ''): ?>
                        <p class="category__note"><?= e($cat['note_' . $lang]) ?></p>
                    <?php endif; ?>

                    <ul class="items">
                        <?php foreach ($cat['items'] as $item):
                            $isAddon = (bool) $item['is_addon'];
                            $soldOut = !$item['available'];
                            $price   = $item['price'];
                        ?>
                            <li class="item<?= $isAddon ? ' item--addon' : '' ?><?= $soldOut ? ' item--soldout' : '' ?>" data-search="<?= e(strtolower($item['name_fa'] . ' ' . $item['name_en'])) ?>">
                                <div class="item__names">
                                    <span class="item__name"><?= e($item['name_' . $lang]) ?></span>
                                    <span class="item__sub" lang="<?= $other ?>"><?= e($item['name_' . $other]) ?></span>
                                </div>
                                <span class="item__dots" aria-hidden="true"></span>
                                <?php if ($soldOut): ?>
                                    <span class="price price--soldout"><?= e(t('unavailable', $lang)) ?></span>
                                <?php elseif ($price === null): ?>
                                    <span class="price price--daily"><?= e(t('daily_price', $lang)) ?></span>
                                <?php else: ?>
                                    <span class="price">
                                        <?php if ($isAddon): ?><small>+</small><?php endif; ?>
                                        <?= num($price, $lang) ?>
                                        <small><?= e(t('unit', $lang)) ?></small>
                                    </span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <p class="empty" id="empty" hidden><?= e(t('no_results', $lang)) ?></p>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
