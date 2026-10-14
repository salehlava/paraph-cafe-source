<?php
/**
 * Café info, customer club rules, admin password.
 */
require __DIR__ . '/_init.php';

$infoKeys = ['name_fa', 'name_en', 'tagline_fa', 'tagline_en', 'address_fa', 'address_en', 'phone', 'hours_fa', 'hours_en', 'instagram'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'info') {
        $values = [];
        foreach ($infoKeys as $k) {
            $values[$k] = post($k);
        }
        if ($values['instagram'] !== '' && !preg_match('#^https?://#i', $values['instagram'])) {
            $values['instagram'] = 'https://instagram.com/' . ltrim($values['instagram'], '@/');
        }
        set_settings($values);
        flash('success', 'اطلاعات کافه ذخیره شد.');
        redirect('settings.php');
    }

    if ($action === 'club') {
        $silver = (int) latin_digits(post('level_silver'));
        $gold   = (int) latin_digits(post('level_gold'));
        if ($silver <= 0 || $gold <= $silver) {
            flash('error', 'امتیاز سطح طلایی باید بیشتر از نقره‌ای باشد.');
        } else {
            set_settings(['club_enabled' => isset($_POST['club_enabled']) ? '1' : '0', 'level_silver' => $silver, 'level_gold' => $gold]);
            flash('success', 'تنظیمات باشگاه ذخیره شد.');
        }
        redirect('settings.php#club');
    }
}

$s = settings_all() + SETTINGS_DEFAULTS;
admin_header('تنظیمات', 'settings');
?>

<section class="card">
    <h2>اطلاعات کافه</h2>
    <p class="muted">این اطلاعات در صفحه شروع، منو و فوتر سایت نمایش داده می‌شود.</p>
    <form method="post" class="form form--grid">
        <?= csrf_field() ?>
        <label class="field"><span>نام کافه (فارسی)</span><input name="name_fa" value="<?= e($s['name_fa']) ?>" required></label>
        <label class="field"><span>نام کافه (انگلیسی)</span><input name="name_en" value="<?= e($s['name_en']) ?>" dir="ltr" required></label>
        <label class="field"><span>شعار (فارسی)</span><input name="tagline_fa" value="<?= e($s['tagline_fa']) ?>"></label>
        <label class="field"><span>شعار (انگلیسی)</span><input name="tagline_en" value="<?= e($s['tagline_en']) ?>" dir="ltr"></label>
        <label class="field"><span>آدرس (فارسی)</span><input name="address_fa" value="<?= e($s['address_fa']) ?>"></label>
        <label class="field"><span>آدرس (انگلیسی)</span><input name="address_en" value="<?= e($s['address_en']) ?>" dir="ltr"></label>
        <label class="field"><span>ساعات کاری (فارسی)</span><input name="hours_fa" value="<?= e($s['hours_fa']) ?>"></label>
        <label class="field"><span>ساعات کاری (انگلیسی)</span><input name="hours_en" value="<?= e($s['hours_en']) ?>" dir="ltr"></label>
        <label class="field"><span>تلفن تماس</span><input name="phone" value="<?= e($s['phone']) ?>" dir="ltr"></label>
        <label class="field"><span>اینستاگرام (لینک یا آیدی)</span><input name="instagram" value="<?= e($s['instagram']) ?>" dir="ltr" placeholder="https://instagram.com/paraf.cafe"></label>
        <div class="form__actions"><button class="btn btn--primary" name="action" value="info">ذخیره اطلاعات</button></div>
    </form>
</section>

<div class="grid-2">
    <section class="card" id="club">
        <h2>باشگاه مشتریان</h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <label class="check"><input type="checkbox" name="club_enabled" <?= $s['club_enabled'] === '1' ? 'checked' : '' ?>> باشگاه مشتریان فعال باشد (نمایش در سایت)</label>
            <label class="field"><span>حداقل امتیاز سطح نقره‌ای</span><input name="level_silver" value="<?= e($s['level_silver']) ?>" dir="ltr" inputmode="numeric"></label>
            <label class="field"><span>حداقل امتیاز سطح طلایی</span><input name="level_gold" value="<?= e($s['level_gold']) ?>" dir="ltr" inputmode="numeric"></label>
            <div class="form__actions"><button class="btn btn--primary" name="action" value="club">ذخیره</button></div>
        </form>
    </section>

    <section class="card" id="password">
        <h2>نام کاربری و رمز عبور</h2>
        <p class="muted">برای تغییر نام کاربری یا رمز عبور مدیر به صفحه «حساب کاربری» بروید.</p>
        <a class="btn btn--primary" href="account.php">رفتن به حساب کاربری</a>
    </section>
</div>

<?php admin_footer(); ?>
