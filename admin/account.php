<?php
/**
 * Admin account: change username and/or password.
 */
require __DIR__ . '/_init.php';

$stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    unset($_SESSION['admin_id']);
    redirect(ADMIN_URL . 'login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = post('username');
    $new      = (string) ($_POST['new'] ?? '');
    $new2     = (string) ($_POST['new2'] ?? '');

    $taken = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ? AND id != ?');
    $taken->execute([$username, $admin['id']]);

    if (!password_verify((string) ($_POST['current'] ?? ''), $admin['password_hash'])) {
        flash('error', 'رمز عبور فعلی اشتباه است.');
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        flash('error', 'نام کاربری باید ۳ تا ۳۲ حرف انگلیسی، عدد یا . _ - باشد.');
    } elseif ((int) $taken->fetchColumn()) {
        flash('error', 'این نام کاربری قبلا استفاده شده است.');
    } elseif ($new !== '' && strlen($new) < 8) {
        flash('error', 'رمز جدید باید حداقل ۸ کاراکتر باشد.');
    } elseif ($new !== $new2) {
        flash('error', 'تکرار رمز جدید یکسان نیست.');
    } elseif ($username === $admin['username'] && $new === '') {
        flash('error', 'تغییری وارد نشده است.');
    } else {
        $hash = $new !== '' ? password_hash($new, PASSWORD_DEFAULT) : $admin['password_hash'];
        db()->prepare('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?')->execute([$username, $hash, $admin['id']]);
        // Keep this browser signed in; every other browser is signed out by the new fingerprint.
        admin_sign_in(['id' => $admin['id'], 'username' => $username, 'password_hash' => $hash]);

        $changed = [];
        if ($username !== $admin['username']) {
            $changed[] = 'نام کاربری';
        }
        if ($new !== '') {
            $changed[] = 'رمز عبور';
        }
        flash('success', implode(' و ', $changed) . ' با موفقیت تغییر کرد. دفعه بعد با اطلاعات جدید وارد شوید.');
    }
    redirect('account.php');
}

admin_header('حساب کاربری', 'account');
?>

<section class="card account">
    <div class="profile">
        <span class="avatar"><?= e(strtoupper(substr($admin['username'], 0, 1))) ?></span>
        <div>
            <h2 dir="ltr" class="ta-end"><?= e($admin['username']) ?></h2>
            <div class="muted">مدیر سایت · ساخته شده در <?= fa_date($admin['created_at']) ?></div>
        </div>
    </div>

    <h3>تغییر نام کاربری و رمز عبور</h3>
    <form method="post" class="form" autocomplete="off">
        <?= csrf_field() ?>
        <label class="field">
            <span>نام کاربری</span>
            <input name="username" value="<?= e($admin['username']) ?>" dir="ltr" autocomplete="username" required>
        </label>
        <label class="field">
            <span>رمز عبور جدید <small class="muted">(اگر نمی‌خواهید عوض شود خالی بگذارید)</small></span>
            <input type="password" name="new" dir="ltr" autocomplete="new-password" minlength="8">
        </label>
        <label class="field">
            <span>تکرار رمز عبور جدید</span>
            <input type="password" name="new2" dir="ltr" autocomplete="new-password">
        </label>
        <hr>
        <label class="field">
            <span>رمز عبور فعلی <small class="muted">(برای تایید تغییرات)</small></span>
            <input type="password" name="current" dir="ltr" autocomplete="current-password" required>
        </label>
        <div class="form__actions">
            <button class="btn btn--primary" type="submit">ذخیره تغییرات</button>
        </div>
    </form>

    <p class="muted small">رمز را فراموش کرده‌اید؟ فایل <code>reset-admin.bat</code> را در پوشه سایت اجرا کنید تا یک رمز جدید ساخته شود.</p>
</section>

<?php admin_footer(); ?>
