<?php
/**
 * Admin login. On the very first visit (no admin yet) it shows a setup form to create one.
 */
define('ADMIN_PUBLIC', true);
require __DIR__ . '/_init.php';

if (admin_logged_in()) {
    redirect(ADMIN_URL . 'index.php');
}

$setup = !(int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$error = null;

// The first admin can only be created from the computer the site runs on,
// so nobody on the internet can claim a freshly uploaded site.
$isLocal   = is_local_request();
$setupDenied = $setup && !$isLocal;

// Brute-force protection (stored in the database, so clearing cookies doesn't help):
// 5 wrong passwords per IP per 15 minutes, 50 per hour for the whole site.
$ipKey  = 'login-fail:ip:' . client_ip();
$limits = [[$ipKey, 5, 900], ['login-fail:all', 50, 3600]];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = post('username');
    $password = (string) ($_POST['password'] ?? '');

    if ($setupDenied) {
        $error = 'ساخت حساب مدیر فقط از روی همان کامپیوتری که سایت روی آن اجرا می‌شود ممکن است.';
    } elseif ($setup) {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
            $error = 'نام کاربری باید ۳ تا ۳۲ حرف انگلیسی یا عدد باشد.';
        } elseif (strlen($password) < 8) {
            $error = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
        } elseif ($password !== ($_POST['password2'] ?? '')) {
            $error = 'تکرار رمز عبور یکسان نیست.';
        } else {
            try {
                db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                flash('success', 'حساب مدیر ساخته شد. اکنون وارد شوید.');
            } catch (PDOException $ex) {
                // Only one admin is allowed — someone already created it.
                flash('error', 'حساب مدیر قبلا ساخته شده است. با همان حساب وارد شوید.');
            }
            redirect(ADMIN_URL . 'login.php');
        }
    } elseif (($wait = rate_check($limits)) > 0) {
        $error = 'تعداد تلاش‌های ناموفق زیاد بود. ' . num((int) ceil($wait / 60), 'fa') . ' دقیقه دیگر دوباره تلاش کنید.';
    } else {
        $stmt = db()->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            admin_sign_in($admin);
            rate_clear($ipKey);
            redirect(ADMIN_URL . 'index.php');
        }

        if (!$admin) {
            password_verify($password, '$2y$10$' . str_repeat('x', 53));   // same timing whether or not the user exists
        }
        rate_hit($ipKey, 'login-fail:all');
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
    }
}

admin_head($setup ? 'ساخت حساب مدیر' : 'ورود');
?>
<body class="auth">
<main class="auth__box">
    <span class="mark mark--lg">P</span>
    <h1><?= $setup ? 'ساخت حساب مدیر' : 'ورود به پنل مدیریت' ?></h1>
    <p class="muted"><?= $setup ? 'اولین بار است که وارد پنل می‌شوید. یک نام کاربری و رمز عبور بسازید.' : e(site('name', 'fa')) ?></p>

    <?php foreach (flashes() as [$type, $msg]): ?>
        <div class="alert alert--<?= e($type) ?>"><?= e($msg) ?></div>
    <?php endforeach; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($setupDenied): ?>
        <div class="alert alert--info">هنوز حساب مدیر ساخته نشده است. برای امنیت، آن را فقط روی کامپیوتر سرور بسازید:
            فایل <code>start-admin.bat</code> را روی همان کامپیوتر اجرا کنید، یا <code>reset-admin.bat</code> را بزنید.</div>
    <?php else: ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label class="field">
            <span>نام کاربری</span>
            <input type="text" name="username" dir="ltr" autocomplete="username" required autofocus value="<?= e(post('username')) ?>">
        </label>
        <label class="field">
            <span>رمز عبور</span>
            <input type="password" name="password" dir="ltr" autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>" required>
        </label>
        <?php if ($setup): ?>
            <label class="field">
                <span>تکرار رمز عبور</span>
                <input type="password" name="password2" dir="ltr" autocomplete="new-password" required>
            </label>
        <?php endif; ?>
        <button class="btn btn--primary btn--block" type="submit"><?= $setup ? 'ساخت حساب' : 'ورود' ?></button>
    </form>
    <?php endif; ?>
    <a class="auth__back" href="../">← بازگشت به سایت</a>
</main>
</body>
</html>
