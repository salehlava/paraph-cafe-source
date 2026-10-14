<?php
require __DIR__ . '/_init.php';

$pdo = db();
$stats = [
    ['اعضای باشگاه', (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn(), 'members.php'],
    ['عضو جدید (۷ روز اخیر)', (int) $pdo->query("SELECT COUNT(*) FROM members WHERE created_at >= datetime('now', '-7 days')")->fetchColumn(), 'members.php'],
    ['آیتم‌های منو', (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn(), 'menu.php'],
    ['پیامک‌های امروز', (int) $pdo->query("SELECT COUNT(*) FROM sms_log WHERE created_at >= date('now')")->fetchColumn(), 'sms.php'],
];
$soldOut = (int) $pdo->query('SELECT COUNT(*) FROM items WHERE available = 0')->fetchColumn();
$recentMembers = $pdo->query('SELECT * FROM members ORDER BY id DESC LIMIT 6')->fetchAll();
$recentSms = $pdo->query('SELECT * FROM sms_log ORDER BY id DESC LIMIT 6')->fetchAll();
$driver = setting('sms_driver');

admin_header('داشبورد', 'dashboard');
?>

<div class="stats">
    <?php foreach ($stats as [$label, $value, $href]): ?>
        <a class="stat" href="<?= $href ?>">
            <span class="stat__value"><?= num(number_format($value), 'fa') ?></span>
            <span class="stat__label"><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($driver === 'log'): ?>
    <div class="alert alert--info">پنل پیامک در <strong>حالت تست</strong> است و پیامکی واقعا ارسال نمی‌شود. برای اتصال به سرویس پیامک به <a href="sms.php">پنل پیامک</a> بروید.</div>
<?php endif; ?>
<?php if ($soldOut): ?>
    <div class="alert alert--warn"><?= num($soldOut, 'fa') ?> آیتم منو «ناموجود» علامت خورده است. <a href="menu.php">مدیریت منو</a></div>
<?php endif; ?>

<div class="grid-2">
    <section class="card">
        <div class="card__head">
            <h2>اعضای جدید</h2>
            <a href="members.php" class="link">همه اعضا</a>
        </div>
        <?php if (!$recentMembers): ?>
            <p class="muted">هنوز عضوی ثبت نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>نام</th><th>موبایل</th><th>امتیاز</th><th>تاریخ</th></tr></thead>
                <tbody>
                <?php foreach ($recentMembers as $m): ?>
                    <tr>
                        <td><a class="link" href="members.php?id=<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a></td>
                        <td dir="ltr" class="ta-end"><?= e(num($m['phone'], 'fa')) ?></td>
                        <td><?= num($m['points'], 'fa') ?></td>
                        <td class="muted"><?= fa_date($m['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="card__head">
            <h2>آخرین پیامک‌ها</h2>
            <a href="sms.php" class="link">پنل پیامک</a>
        </div>
        <?php if (!$recentSms): ?>
            <p class="muted">هنوز پیامکی ارسال نشده است.</p>
        <?php else: ?>
            <ul class="log">
                <?php foreach ($recentSms as $s): ?>
                    <li>
                        <span class="badge badge--<?= $s['ok'] ? 'ok' : 'err' ?>"><?= $s['ok'] ? 'موفق' : 'ناموفق' ?></span>
                        <span class="log__msg"><?= e(mb_strimwidth_safe($s['message'], 70)) ?></span>
                        <span class="muted"><?= fa_date($s['created_at'], true) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php admin_footer();

function mb_strimwidth_safe(string $s, int $len): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($s, 0, $len, '…', 'UTF-8');
    }
    return preg_match('/^.{0,' . $len . '}/u', $s, $m) ? $m[0] . (strlen($m[0]) < strlen($s) ? '…' : '') : $s;
}
