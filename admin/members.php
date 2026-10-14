<?php
/**
 * Customer club members: list, search, add, edit, points, SMS, CSV export.
 */
require __DIR__ . '/_init.php';

$pdo = db();

/* ---------- CSV export (opens in Excel) ---------- */
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="members-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, ['ID', 'Name', 'Phone', 'Birthday', 'Points', 'Level', 'Note', 'Joined']);
    foreach ($pdo->query('SELECT * FROM members ORDER BY id') as $m) {
        fputcsv($out, array_map('csv_safe', [$m['id'], $m['name'], $m['phone'], $m['birthday'], $m['points'], member_level((int) $m['points']), $m['note'], $m['created_at']]));
    }
    exit;
}

/* ---------- Actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');
    $id     = (int) post('id');
    $back   = $id ? 'members.php?id=' . $id : 'members.php';

    switch ($action) {
        case 'add':
            $phone = normalize_phone(post('phone'));
            if (!$phone || post('name') === '') {
                flash('error', 'نام و شماره موبایل معتبر (۰۹xxxxxxxxx) را وارد کنید.');
            } elseif (member_by_phone($phone)) {
                flash('error', 'این شماره قبلا عضو شده است.');
            } else {
                $pdo->prepare('INSERT INTO members (phone, name, birthday, note) VALUES (?, ?, ?, ?)')
                    ->execute([$phone, post('name'), latin_digits(post('birthday')), post('note')]);
                $newId = (int) $pdo->lastInsertId();
                $pts = (int) latin_digits(post('points', '0'));
                if ($pts) {
                    member_add_points($newId, $pts, 'امتیاز اولیه');
                }
                if (isset($_POST['send_welcome']) && setting('sms_welcome_tpl') !== '') {
                    sms_send($phone, sms_template((string) setting('sms_welcome_tpl'), member_by_phone($phone)));
                }
                flash('success', 'عضو جدید اضافه شد.');
                $back = 'members.php?id=' . $newId;
            }
            break;

        case 'points':
            $delta = (int) latin_digits(post('delta'));
            if ($delta === 0) {
                flash('error', 'تعداد امتیاز را وارد کنید (برای کسر، عدد منفی).');
                break;
            }
            member_add_points($id, $delta, post('reason'));
            flash('success', ($delta > 0 ? 'امتیاز اضافه شد: ' : 'امتیاز کسر شد: ') . num(abs($delta), 'fa'));
            if (post('return') === 'list') {
                $back = 'members.php' . (isset($_GET['q']) ? '?q=' . urlencode((string) $_GET['q']) : '');
            }
            break;

        case 'save':
            $phone = normalize_phone(post('phone'));
            $dup = $phone ? member_by_phone($phone) : null;
            if (!$phone || post('name') === '') {
                flash('error', 'نام و شماره موبایل معتبر را وارد کنید.');
            } elseif ($dup && (int) $dup['id'] !== $id) {
                flash('error', 'این شماره متعلق به عضو دیگری است.');
            } else {
                $pdo->prepare('UPDATE members SET name = ?, phone = ?, birthday = ?, note = ? WHERE id = ?')
                    ->execute([post('name'), $phone, latin_digits(post('birthday')), post('note'), $id]);
                flash('success', 'اطلاعات عضو ذخیره شد.');
            }
            break;

        case 'sms':
            $m = $pdo->prepare('SELECT * FROM members WHERE id = ?');
            $m->execute([$id]);
            if (($m = $m->fetch()) && post('message') !== '') {
                $r = sms_send($m['phone'], sms_template(post('message'), $m));
                flash($r['ok'] ? 'success' : 'error', $r['ok'] ? 'پیامک ارسال شد.' : 'ارسال ناموفق: ' . $r['response']);
            }
            break;

        case 'delete':
            $pdo->prepare('DELETE FROM members WHERE id = ?')->execute([$id]);
            flash('success', 'عضو حذف شد.');
            $back = 'members.php';
            break;
    }
    redirect($back);
}

/* ---------- Single member view ---------- */
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $m = $stmt->fetch();
    if (!$m) {
        flash('error', 'عضو پیدا نشد.');
        redirect('members.php');
    }
    $log = $pdo->prepare('SELECT * FROM points_log WHERE member_id = ? ORDER BY id DESC LIMIT 100');
    $log->execute([$m['id']]);
    $log = $log->fetchAll();
    $level = member_level((int) $m['points']);

    admin_header($m['name'], 'members');
    ?>
    <a class="link back" href="members.php">→ بازگشت به لیست اعضا</a>

    <div class="grid-2">
        <section class="card">
            <div class="profile">
                <span class="avatar"><?= e(function_exists('mb_substr') ? mb_substr($m['name'], 0, 1) : '') ?></span>
                <div>
                    <h2><?= e($m['name']) ?></h2>
                    <div class="muted" dir="ltr"><?= e(num($m['phone'], 'fa')) ?></div>
                </div>
                <div class="profile__pts">
                    <strong><?= num(number_format((int) $m['points']), 'fa') ?></strong>
                    <span class="badge badge--<?= $level ?>"><?= e(LEVELS[$level]['fa']) ?></span>
                </div>
            </div>

            <h3>افزودن / کسر امتیاز</h3>
            <form method="post" class="form form--inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <input name="delta" placeholder="مثلا 50 یا -20" inputmode="numeric" dir="ltr" required>
                <input name="reason" placeholder="دلیل (مثلا خرید ۲۵۰ هزار تومانی)">
                <button class="btn btn--primary" name="action" value="points">ثبت</button>
            </form>

            <h3>ارسال پیامک به این عضو</h3>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <textarea name="message" rows="3" placeholder="{name} عزیز، ..." required></textarea>
                <div class="form__actions"><button class="btn" name="action" value="sms">ارسال پیامک</button></div>
            </form>
        </section>

        <section class="card">
            <h3>ویرایش اطلاعات</h3>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <label class="field"><span>نام</span><input name="name" value="<?= e($m['name']) ?>" required></label>
                <label class="field"><span>موبایل</span><input name="phone" value="<?= e($m['phone']) ?>" dir="ltr" required></label>
                <label class="field"><span>تاریخ تولد</span><input name="birthday" value="<?= e($m['birthday']) ?>" dir="ltr" placeholder="1375/05/12"></label>
                <label class="field"><span>یادداشت</span><textarea name="note" rows="2"><?= e($m['note']) ?></textarea></label>
                <div class="form__actions">
                    <button class="btn btn--primary" name="action" value="save">ذخیره</button>
                    <button class="btn btn--danger" name="action" value="delete" data-confirm="عضو «<?= e($m['name']) ?>» حذف شود؟" formnovalidate>حذف عضو</button>
                </div>
                <p class="muted">عضویت از <?= fa_date($m['created_at']) ?></p>
            </form>
        </section>
    </div>

    <section class="card">
        <h3>تاریخچه امتیازها</h3>
        <?php if (!$log): ?>
            <p class="muted">هنوز امتیازی ثبت نشده است.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>تاریخ</th><th>امتیاز</th><th>دلیل</th></tr></thead>
                <tbody>
                <?php foreach ($log as $row): ?>
                    <tr>
                        <td class="muted"><?= fa_date($row['created_at'], true) ?></td>
                        <td dir="ltr" class="ta-end <?= $row['delta'] > 0 ? 'pos' : 'neg' ?>"><?= ($row['delta'] > 0 ? '+' : '') . num($row['delta'], 'fa') ?></td>
                        <td><?= e($row['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <?php
    admin_footer();
    exit;
}

/* ---------- List ---------- */
$q       = trim(latin_digits((string) ($_GET['q'] ?? '')));
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$where   = '';
$params  = [];
if ($q !== '') {
    $where  = 'WHERE name LIKE ? OR phone LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}
$count = $pdo->prepare("SELECT COUNT(*) FROM members $where");
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));

$list = $pdo->prepare("SELECT * FROM members $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
$list->execute($params);
$members = $list->fetchAll();

admin_header('باشگاه مشتریان', 'members');
?>

<div class="toolbar">
    <form method="get" class="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجوی نام یا موبایل…">
        <button class="btn">جستجو</button>
    </form>
    <div class="toolbar__right">
        <a class="btn" href="members.php?export=csv">خروجی اکسل (CSV)</a>
        <a class="btn btn--primary" href="#add">+ عضو جدید</a>
    </div>
</div>

<section class="card">
    <div class="card__head">
        <h2><?= num(number_format($total), 'fa') ?> عضو</h2>
        <span class="muted">سطح‌ها: نقره‌ای از <?= num(setting('level_silver'), 'fa') ?> و طلایی از <?= num(setting('level_gold'), 'fa') ?> امتیاز (<a class="link" href="settings.php#club">تغییر</a>)</span>
    </div>
    <?php if (!$members): ?>
        <p class="muted"><?= $q !== '' ? 'نتیجه‌ای پیدا نشد.' : 'هنوز عضوی ثبت نشده است. مشتری‌ها از صفحه «باشگاه مشتریان» سایت عضو می‌شوند.' ?></p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>نام</th><th>موبایل</th><th>امتیاز</th><th>سطح</th><th>عضویت</th><th>امتیاز سریع</th></tr></thead>
                <tbody>
                <?php foreach ($members as $m): $level = member_level((int) $m['points']); ?>
                    <tr>
                        <td><a class="link" href="members.php?id=<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a></td>
                        <td dir="ltr" class="ta-end"><?= e(num($m['phone'], 'fa')) ?></td>
                        <td><strong><?= num(number_format((int) $m['points']), 'fa') ?></strong></td>
                        <td><span class="badge badge--<?= $level ?>"><?= e(LEVELS[$level]['fa']) ?></span></td>
                        <td class="muted"><?= fa_date($m['created_at']) ?></td>
                        <td>
                            <form method="post" class="quick-points" action="members.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                <input type="hidden" name="return" value="list">
                                <input name="delta" inputmode="numeric" dir="ltr" placeholder="+۱۰" aria-label="امتیاز" required>
                                <button class="btn btn--sm" name="action" value="points">ثبت</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="pager">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <a href="?page=<?= $p ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="<?= $p === $page ? 'is-active' : '' ?>"><?= num($p, 'fa') ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="card" id="add">
    <h2>افزودن عضو جدید</h2>
    <form method="post" class="form form--grid">
        <?= csrf_field() ?>
        <label class="field"><span>نام و نام خانوادگی</span><input name="name" required></label>
        <label class="field"><span>موبایل</span><input name="phone" dir="ltr" placeholder="09xxxxxxxxx" required></label>
        <label class="field"><span>تاریخ تولد (اختیاری)</span><input name="birthday" dir="ltr" placeholder="1375/05/12"></label>
        <label class="field"><span>امتیاز اولیه</span><input name="points" dir="ltr" inputmode="numeric" value="0"></label>
        <label class="field field--wide"><span>یادداشت</span><input name="note"></label>
        <label class="check"><input type="checkbox" name="send_welcome" checked> ارسال پیامک خوش‌آمد</label>
        <div class="form__actions"><button class="btn btn--primary" name="action" value="add">افزودن عضو</button></div>
    </form>
</section>

<?php admin_footer(); ?>
