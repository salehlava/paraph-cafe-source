<?php /** @var string $lang */ ?>
<footer class="footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <span class="brand__mark" aria-hidden="true">P</span>
            <div>
                <strong><?= e(site('name', $lang)) ?></strong>
                <p><?= e(site('tagline', $lang)) ?></p>
            </div>
        </div>
        <div>
            <h4><?= e(t('hours', $lang)) ?></h4>
            <p><?= e(site('hours', $lang)) ?></p>
        </div>
        <div>
            <h4><?= e(t('address', $lang)) ?></h4>
            <p><?= e(site('address', $lang)) ?></p>
        </div>
        <div>
            <h4><?= e(t('contact', $lang)) ?></h4>
            <p><a href="tel:<?= e(preg_replace('/[^\d+]/', '', site('phone'))) ?>" dir="ltr"><?= e(num(site('phone'), $lang)) ?></a></p>
            <p><a href="<?= e(site('instagram')) ?>" target="_blank" rel="noopener">Instagram</a></p>
            <?php if (setting('club_enabled') === '1'): ?>
                <p><a href="club.php?lang=<?= e($lang) ?>"><?= e(t('club', $lang)) ?></a></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="container footer__bottom">
        © <?= num(date('Y'), $lang) ?> <?= e(site('name', $lang)) ?> — <?= e(t('rights', $lang)) ?>
    </div>
</footer>

<button class="to-top" id="toTop" type="button" aria-label="<?= e(t('top', $lang)) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>

<script src="assets/js/main.js?v=3" defer></script>
</body>
</html>
