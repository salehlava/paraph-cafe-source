<?php
/**
 * Appearance: site colours (ready-made themes + custom colour pickers, with live preview).
 */
require __DIR__ . '/_init.php';

const COLOR_LABELS = [
    'color_primary'  => ['رنگ اصلی', 'دکمه‌ها، آیکون دسته‌ها، تب انتخاب‌شده'],
    'color_dark'     => ['رنگ تیره', 'نوار بالا، سربرگ منو، صفحه شروع'],
    'color_accent'   => ['رنگ تاکیدی', 'دکمه باشگاه، نوارهای تزئینی، لینک‌ها'],
    'color_cream'    => ['رنگ کرم', 'برچسب قیمت، متن روشن روی زمینه تیره'],
    'color_bg_light' => ['پس‌زمینه حالت روشن', 'رنگ زمینه صفحه در حالت روز'],
    'color_bg_dark'  => ['پس‌زمینه حالت تیره', 'رنگ زمینه صفحه در حالت شب'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (post('action') === 'reset') {
        set_settings(array_intersect_key(SETTINGS_DEFAULTS, array_flip(COLOR_KEYS)));
        flash('success', 'رنگ‌ها به حالت پیش‌فرض (شکلاتی) برگشت.');
    } else {
        $values = [];
        foreach (COLOR_KEYS as $k) {
            $v = strtolower(post($k));
            if (!preg_match('/^#[0-9a-f]{6}$/', $v)) {
                flash('error', 'کد رنگ «' . COLOR_LABELS[$k][0] . '» معتبر نیست (مثال: #4a2c1d).');
                redirect('appearance.php');
            }
            $values[$k] = $v;
        }
        set_settings($values);
        flash('success', 'رنگ‌های سایت ذخیره شد.');
    }
    redirect('appearance.php');
}

$current = [];
foreach (COLOR_KEYS as $k) {
    $current[$k] = theme_color($k);
}

admin_header('ظاهر و رنگ‌ها', 'appearance');
?>

<section class="card">
    <div class="card__head">
        <h2>تم‌های آماده</h2>
        <span class="muted">روی یک تم کلیک کنید، پیش‌نمایش را ببینید و سپس «ذخیره» را بزنید.</span>
    </div>
    <div class="presets">
        <?php foreach (COLOR_PRESETS as $key => $preset): $name = array_shift($preset); ?>
            <button type="button" class="preset" data-colors="<?= e(implode(',', $preset)) ?>">
                <span class="preset__swatches">
                    <?php foreach (array_slice($preset, 0, 4) as $hex): ?><i style="background: <?= e($hex) ?>"></i><?php endforeach; ?>
                </span>
                <span class="preset__name"><?= e($name) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<form method="post" id="colorsForm">
    <?= csrf_field() ?>
    <div class="grid-2">
        <section class="card">
            <h2>رنگ‌های دلخواه</h2>
            <div class="colors">
                <?php foreach (COLOR_LABELS as $k => [$label, $hint]): ?>
                    <label class="color-row">
                        <input type="color" value="<?= e($current[$k]) ?>" data-key="<?= $k ?>" aria-label="<?= e($label) ?>">
                        <span class="color-row__text">
                            <strong><?= e($label) ?></strong>
                            <small class="muted"><?= e($hint) ?></small>
                        </span>
                        <input type="text" name="<?= $k ?>" value="<?= e($current[$k]) ?>" data-hex="<?= $k ?>" dir="ltr" maxlength="7" pattern="#[0-9a-fA-F]{6}" required>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form__actions" style="margin-top: 18px">
                <button class="btn btn--primary" type="submit" name="action" value="save">ذخیره رنگ‌ها</button>
                <button class="btn" type="button" id="undo">لغو تغییرات</button>
                <button class="btn btn--danger" type="submit" name="action" value="reset" formnovalidate data-confirm="رنگ‌ها به حالت پیش‌فرض شکلاتی برگردد؟">بازگشت به پیش‌فرض</button>
            </div>
        </section>

        <section class="card">
            <div class="card__head">
                <h2>پیش‌نمایش</h2>
                <a class="link" href="../?lang=fa" target="_blank">مشاهده سایت</a>
            </div>
            <div class="previews" id="preview">
                <?php foreach (['light' => 'حالت روشن', 'dark' => 'حالت تیره'] as $mode => $modeLabel): ?>
                    <div class="pv pv--<?= $mode ?>">
                        <div class="pv__bar"><span class="pv__logo">P</span><span><?= e(site('name', 'fa')) ?></span><span class="pv__club">باشگاه</span></div>
                        <div class="pv__hero"><b><?= e(site('name', 'fa')) ?></b><small><?= e($modeLabel) ?></small></div>
                        <div class="pv__body">
                            <div class="pv__chips"><span class="on">قهوه</span><span>چای</span><span>کیک</span></div>
                            <div class="pv__card">
                                <div class="pv__cat"><span class="pv__icon">☕</span><b>نوشیدنی گرم</b></div>
                                <div class="pv__item"><span>لاته</span><i></i><em>۲۰۰</em></div>
                                <div class="pv__item"><span>موکا</span><i></i><em>۲۲۰</em></div>
                                <div class="pv__btn">دکمه</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</form>

<script>
    (function () {
        var form = document.getElementById('colorsForm');
        var preview = document.getElementById('preview');
        var keys = <?= json_encode(COLOR_KEYS) ?>;
        var saved = <?= json_encode(array_values($current)) ?>;
        var varNames = { color_primary: '--p', color_dark: '--d', color_accent: '--a', color_cream: '--c', color_bg_light: '--l', color_bg_dark: '--k' };

        function picker(k) { return form.querySelector('[data-key="' + k + '"]'); }
        function hex(k) { return form.querySelector('[data-hex="' + k + '"]'); }

        function apply() {
            keys.forEach(function (k) {
                var v = hex(k).value.trim();
                if (/^#[0-9a-fA-F]{6}$/.test(v)) preview.style.setProperty(varNames[k], v);
            });
        }
        function setAll(list) {
            keys.forEach(function (k, i) { picker(k).value = list[i]; hex(k).value = list[i]; });
            apply();
        }

        keys.forEach(function (k) {
            picker(k).addEventListener('input', function () { hex(k).value = picker(k).value; apply(); });
            hex(k).addEventListener('input', function () {
                var v = hex(k).value.trim();
                if (/^#[0-9a-fA-F]{6}$/.test(v)) { picker(k).value = v.toLowerCase(); apply(); }
            });
        });

        document.querySelectorAll('.preset').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('.preset').forEach(function (x) { x.classList.remove('is-active'); });
                b.classList.add('is-active');
                setAll(b.getAttribute('data-colors').split(','));
            });
        });
        document.getElementById('undo').addEventListener('click', function () { setAll(saved); });

        apply();
    })();
</script>

<?php admin_footer(); ?>
