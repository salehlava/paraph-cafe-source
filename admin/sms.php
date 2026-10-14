<?php
/**
 * SMS panel: provider settings, test send, bulk send to club members, log.
 */
require __DIR__ . '/_init.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    switch ($action) {
        case 'settings':
            $driver = array_key_exists(post('sms_driver'), SMS_DRIVERS) ? post('sms_driver') : 'log';
            $values = [
                'sms_driver'      => $driver,
                'sms_api_key'     => post('sms_api_key'),
                'sms_sender'      => post('sms_sender'),
                'sms_username'    => post('sms_username'),
                'sms_otp_tpl'     => post('sms_otp_tpl'),
                'sms_welcome_tpl' => post('sms_welcome_tpl'),
            ];
            // Keep the stored password unless a new one is typed.
            if (post('sms_password') !== '') {
                $values['sms_password'] = post('sms_password');
            }
            if (strpos($values['sms_otp_tpl'], '{code}') === false) {
                flash('error', 'متن پیامک کد تایید باید شامل {code} باشد.');
                break;
            }
            set_settings($values);
            flash('success', 'تنظیمات پیامک ذخیره شد.');
            break;

        case 'test':
            $phone = normalize_phone(post('phone'));
            if (!$phone) {
                flash('error', 'شماره موبایل معتبر نیست.');
                break;
            }
            $r = sms_send($phone, post('message') ?: 'پیامک تست از ' . site('name', 'fa'));
            flash($r['ok'] ? 'success' : 'error', ($r['ok'] ? 'ارسال شد. ' : 'ارسال ناموفق. ') . 'پاسخ سرویس: ' . $r['response']);
            break;

        case 'bulk':
            $message = post('message');
            $target  = post('target');
            if ($message === '') {
                flash('error', 'متن پیام را وارد کنید.');
                break;
            }

            if ($target === 'custom') {
                $phones = [];
                foreach (preg_split('/[\s,،;]+/u', latin_digits(post('numbers'))) as $p) {
                    if ($n = normalize_phone($p)) {
                        $phones[] = $n;
                    }
                }
                $recipients = array_map(fn($p) => ['phone' => $p, 'name' => '', 'points' => 0], array_unique($phones));
            } else {
                $recipients = $pdo->query('SELECT * FROM members ORDER BY id')->fetchAll();
                if (in_array($target, ['bronze', 'silver', 'gold'], true)) {
                    $recipients = array_values(array_filter($recipients, fn($m) => member_level((int) $m['points']) === $target));
                }
            }

            if (!$recipients) {
                flash('error', 'هیچ گیرنده‌ای پیدا نشد.');
                break;
            }

            // Personalised messages are sent one by one; plain messages in one bulk request.
            $personal = strpos($message, '{name}') !== false || strpos($message, '{points}') !== false;
            $sent = 0;
            $failed = 0;
            if ($personal) {
                @set_time_limit(0);
                foreach ($recipients as $m) {
                    sms_send($m['phone'], sms_template($message, $m))['ok'] ? $sent++ : $failed++;
                }
            } else {
                $r = sms_send(array_column($recipients, 'phone'), $message);
                if ($r['ok']) {
                    $sent = count($recipients);
                } else {
                    $failed = count($recipients);
                }
            }
            flash($failed ? 'error' : 'success', 'ارسال به ' . num($sent, 'fa') . ' نفر انجام شد' . ($failed ? ' — ' . num($failed, 'fa') . ' ناموفق (جزئیات در گزارش پایین صفحه)' : '') . '.');
            break;

        case 'clear_log':
            $pdo->exec('DELETE FROM sms_log');
            flash('success', 'گزارش پیامک‌ها پاک شد.');
            break;
    }
    redirect('sms.php');
}

$s = settings_all() + SETTINGS_DEFAULTS;
$memberCount = (int) $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$log = $pdo->query('SELECT * FROM sms_log ORDER BY id DESC LIMIT 50')->fetchAll();
$canHttps = function_exists('curl_init') || extension_loaded('openssl');

admin_header('پنل پیامک', 'sms');
?>

<?php if (!$canHttps && $s['sms_driver'] !== 'log'): ?>
    <div class="alert alert--error">افزونه‌های <code>curl</code> یا <code>openssl</code> در PHP فعال نیستند و ارسال پیامک کار نمی‌کند. سایت را با <code>start.bat</code> اجرا کنید یا آن‌ها را در php.ini فعال کنید.</div>
<?php endif; ?>

<div class="grid-2">
    <section class="card">
        <h2>اتصال به سرویس پیامک</h2>
        <form method="post" class="form" id="smsSettings">
            <?= csrf_field() ?>
            <label class="field">
                <span>سرویس‌دهنده</span>
                <select name="sms_driver" id="smsDriver">
                    <?php foreach (SMS_DRIVERS as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $s['sms_driver'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field" data-for="kavenegar smsir ippanel">
                <span>کلید API (API Key)</span>
                <input name="sms_api_key" value="<?= e($s['sms_api_key']) ?>" dir="ltr" autocomplete="off">
            </label>
            <label class="field" data-for="melipayamak">
                <span>نام کاربری پنل</span>
                <input name="sms_username" value="<?= e($s['sms_username']) ?>" dir="ltr" autocomplete="off">
            </label>
            <label class="field" data-for="melipayamak">
                <span>رمز عبور پنل <?= $s['sms_password'] !== '' ? '(ذخیره شده — برای تغییر، رمز جدید بنویسید)' : '' ?></span>
                <input type="password" name="sms_password" dir="ltr" autocomplete="new-password">
            </label>
            <label class="field" data-for="kavenegar smsir melipayamak ippanel">
                <span>شماره خط فرستنده</span>
                <input name="sms_sender" value="<?= e($s['sms_sender']) ?>" dir="ltr" placeholder="مثلا 3000xxxx">
            </label>
            <label class="field">
                <span>متن پیامک کد تایید (باید شامل <code>{code}</code> باشد)</span>
                <textarea name="sms_otp_tpl" rows="2"><?= e($s['sms_otp_tpl']) ?></textarea>
            </label>
            <label class="field">
                <span>متن پیامک خوش‌آمد عضو جدید (خالی = ارسال نشود)</span>
                <textarea name="sms_welcome_tpl" rows="2"><?= e($s['sms_welcome_tpl']) ?></textarea>
            </label>
            <p class="muted">در متن‌ها می‌توانید از <code>{name}</code> (نام)، <code>{points}</code> (امتیاز) و <code>{phone}</code> استفاده کنید.</p>
            <div class="form__actions"><button class="btn btn--primary" name="action" value="settings">ذخیره تنظیمات</button></div>
        </form>
    </section>

    <div>
        <section class="card">
            <h2>ارسال پیامک تست</h2>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <label class="field"><span>موبایل</span><input name="phone" dir="ltr" placeholder="09xxxxxxxxx" required></label>
                <label class="field"><span>متن (اختیاری)</span><input name="message" placeholder="پیامک تست"></label>
                <div class="form__actions"><button class="btn" name="action" value="test">ارسال تست</button></div>
            </form>
        </section>

        <section class="card">
            <h2>ارسال گروهی</h2>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <label class="field">
                    <span>گیرندگان</span>
                    <select name="target" id="bulkTarget">
                        <option value="all">همه اعضای باشگاه (<?= num($memberCount, 'fa') ?> نفر)</option>
                        <option value="bronze">فقط سطح برنزی</option>
                        <option value="silver">فقط سطح نقره‌ای</option>
                        <option value="gold">فقط سطح طلایی</option>
                        <option value="custom">شماره‌های دلخواه</option>
                    </select>
                </label>
                <label class="field" id="customNumbers" hidden>
                    <span>شماره‌ها (هر خط یک شماره)</span>
                    <textarea name="numbers" rows="3" dir="ltr"></textarea>
                </label>
                <label class="field">
                    <span>متن پیام</span>
                    <textarea name="message" rows="4" id="bulkMsg" placeholder="{name} عزیز، امروز ۲۰٪ تخفیف ویژه اعضای باشگاه ☕" required></textarea>
                    <small class="muted" id="bulkCount"></small>
                </label>
                <div class="form__actions">
                    <button class="btn btn--primary" name="action" value="bulk" data-confirm="پیام برای گیرندگان انتخاب‌شده ارسال شود؟">ارسال گروهی</button>
                </div>
            </form>
        </section>
    </div>
</div>

<section class="card">
    <div class="card__head">
        <h2>گزارش ارسال‌ها</h2>
        <?php if ($log): ?>
            <form method="post"><?= csrf_field() ?><button class="btn btn--sm btn--danger" name="action" value="clear_log" data-confirm="همه گزارش‌ها پاک شود؟">پاک کردن گزارش</button></form>
        <?php endif; ?>
    </div>
    <?php if (!$log): ?>
        <p class="muted">هنوز پیامکی ارسال نشده است.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>زمان</th><th>وضعیت</th><th>گیرنده</th><th>متن</th><th>سرویس</th><th>پاسخ</th></tr></thead>
                <tbody>
                <?php foreach ($log as $row): $rc = explode(',', $row['recipients']); ?>
                    <tr>
                        <td class="muted nowrap"><?= fa_date($row['created_at'], true) ?></td>
                        <td><span class="badge badge--<?= $row['ok'] ? 'ok' : 'err' ?>"><?= $row['ok'] ? 'موفق' : 'ناموفق' ?></span></td>
                        <td dir="ltr" class="ta-end nowrap"><?= count($rc) > 1 ? num(count($rc), 'fa') . ' نفر' : e(num($rc[0], 'fa')) ?></td>
                        <td class="msg"><?= e($row['message']) ?></td>
                        <td class="muted"><?= e($row['driver']) ?></td>
                        <td class="muted small" dir="ltr"><?= e($row['response']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<script>
    (function () {
        // Show only the credential fields the selected provider needs.
        var sel = document.getElementById('smsDriver');
        function sync() {
            document.querySelectorAll('#smsSettings [data-for]').forEach(function (el) {
                el.hidden = el.getAttribute('data-for').split(' ').indexOf(sel.value) === -1;
            });
        }
        sel.addEventListener('change', sync); sync();

        var tgt = document.getElementById('bulkTarget'), custom = document.getElementById('customNumbers');
        tgt.addEventListener('change', function () { custom.hidden = tgt.value !== 'custom'; });

        // Persian SMS: 70 chars per part (67 when multi-part).
        var msg = document.getElementById('bulkMsg'), cnt = document.getElementById('bulkCount');
        msg.addEventListener('input', function () {
            var n = msg.value.length, parts = n <= 70 ? 1 : Math.ceil(n / 67);
            cnt.textContent = n ? n + ' کاراکتر — ' + parts + ' پیامک' : '';
        });
    })();
</script>

<?php admin_footer(); ?>
