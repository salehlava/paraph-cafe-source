<?php
/**
 * Customer club: sign in with mobile + SMS code, join, and see points.
 * Steps (kept in the session): phone → verify → register (new customers) → member.
 */
require __DIR__ . '/lib/bootstrap.php';

$lang  = current_lang();
$self  = 'club.php?lang=' . $lang;
start_session();
$club = &$_SESSION['club'];
$club = $club ?? ['step' => 'phone'];

if (!club_enabled()) {
    $club = ['step' => 'closed'];
}

/**
 * SMS limits (stored in the database, so clearing cookies doesn't bypass them):
 * per visitor IP 5/hour, per phone number 5/day, whole site 200/hour (protects SMS credit).
 */
function club_send_limits(string $phone): array
{
    return [
        ['otp-send:ip:' . client_ip(), 5, 3600],
        ['otp-send:phone:' . $phone, 5, 86400],
        ['otp-send:all', 200, 3600],
    ];
}

function club_send_code(array &$club, string $phone): void
{
    $limits = club_send_limits($phone);
    if (rate_check($limits) > 0) {
        $club['error'] = ['err_limit'];
        return;
    }
    $res = otp_send($phone);
    if ($res['error'] !== 'wait') {
        rate_hit(...array_column($limits, 0));   // count only real send attempts
    }
    if ($res['ok']) {
        $club['step']      = 'verify';
        $club['phone']     = $phone;
        $club['test_code'] = $res['test_code'];
    } elseif ($res['error'] === 'wait') {
        $club['error'] = ['err_wait', $res['wait']];
    } else {
        $club['error'] = ['err_sms'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $club['step'] !== 'closed') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'send') {
        $phone = normalize_phone((string) ($_POST['phone'] ?? ''));
        if (!$phone) {
            $club['error'] = ['err_phone'];
        } else {
            club_send_code($club, $phone);
        }
    } elseif ($action === 'resend' && !empty($club['phone']) && $club['step'] === 'verify') {
        club_send_code($club, $club['phone']);
    } elseif ($action === 'verify' && $club['step'] === 'verify') {
        $ipKey = 'otp-verify:ip:' . client_ip();
        if (rate_check([[$ipKey, 20, 3600]]) > 0) {
            $club['error'] = ['err_limit'];
        } elseif (otp_verify($club['phone'], (string) ($_POST['code'] ?? ''))) {
            session_regenerate_id(true);
            unset($club['test_code']);
            $club['step'] = member_by_phone($club['phone']) ? 'member' : 'register';
        } else {
            rate_hit($ipKey);
            $club['error'] = ['err_code'];
        }
    } elseif ($action === 'register' && $club['step'] === 'register') {
        $name     = trim((string) ($_POST['name'] ?? ''));
        $birthday = trim(latin_digits((string) ($_POST['birthday'] ?? '')));
        if ($name === '' || strlen($name) > 120) {
            $club['error'] = ['err_name'];
        } else {
            if (!member_by_phone($club['phone'])) {
                db()->prepare('INSERT INTO members (phone, name, birthday) VALUES (?, ?, ?)')
                    ->execute([$club['phone'], $name, substr($birthday, 0, 20)]);
                $tpl = (string) setting('sms_welcome_tpl');
                if ($tpl !== '') {
                    sms_send($club['phone'], sms_template($tpl, member_by_phone($club['phone'])));
                }
            }
            $club['step'] = 'member';
        }
    } elseif ($action === 'reset') {
        $club = ['step' => 'phone'];
    }

    redirect($self);
}

$error = $club['error'] ?? null;
unset($club['error']);
$errorText = $error ? sprintf(t($error[0], $lang), num($error[1] ?? '', $lang)) : null;

$member = ($club['step'] === 'member') ? member_by_phone($club['phone']) : null;
if ($club['step'] === 'member' && !$member) {
    $club = ['step' => 'phone'];   // member was deleted by admin
}

$pageTitle = t('club', $lang);
require __DIR__ . '/includes/header.php';
?>

<main class="club">
    <section class="club__hero">
        <div class="container">
            <span class="club__badge"><?= icon('gift') ?></span>
            <h1><?= e(t('club', $lang)) ?></h1>
            <p><?= e(t('club_intro', $lang)) ?></p>
        </div>
    </section>

    <div class="container">
        <div class="club__panel">

            <?php if ($errorText): ?>
                <div class="alert alert--error" role="alert"><?= e($errorText) ?></div>
            <?php endif; ?>

            <?php if ($club['step'] === 'closed'): ?>
                <p class="club__text"><?= e(t('club_closed', $lang)) ?></p>

            <?php elseif ($club['step'] === 'phone'): ?>
                <form method="post" action="<?= e($self) ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="send">
                    <label class="field">
                        <span><?= e(t('mobile', $lang)) ?></span>
                        <input type="tel" name="phone" inputmode="numeric" autocomplete="tel" placeholder="09xx xxx xxxx" dir="ltr" required autofocus>
                    </label>
                    <button class="btn btn--primary" type="submit"><?= e(t('send_code', $lang)) ?></button>
                </form>

            <?php elseif ($club['step'] === 'verify'): ?>
                <p class="club__text"><?= e(sprintf(t('code_sent', $lang), num($club['phone'], $lang))) ?></p>
                <?php if (!empty($club['test_code'])): ?>
                    <div class="alert alert--info"><?= e(t('test_mode', $lang)) ?> <strong dir="ltr"><?= e($club['test_code']) ?></strong></div>
                <?php endif; ?>
                <form method="post" action="<?= e($self) ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="verify">
                    <label class="field">
                        <span><?= e(t('code', $lang)) ?></span>
                        <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="5" class="code-input" dir="ltr" required autofocus>
                    </label>
                    <button class="btn btn--primary" type="submit"><?= e(t('verify', $lang)) ?></button>
                </form>
                <div class="club__links">
                    <form method="post" action="<?= e($self) ?>"><?= csrf_field() ?><button class="link-btn" name="action" value="resend"><?= e(t('resend', $lang)) ?></button></form>
                    <form method="post" action="<?= e($self) ?>"><?= csrf_field() ?><button class="link-btn" name="action" value="reset"><?= e(t('change_number', $lang)) ?></button></form>
                </div>

            <?php elseif ($club['step'] === 'register'): ?>
                <p class="club__text"><?= e(t('welcome_new', $lang)) ?></p>
                <form method="post" action="<?= e($self) ?>" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="register">
                    <label class="field">
                        <span><?= e(t('your_name', $lang)) ?></span>
                        <input type="text" name="name" maxlength="80" autocomplete="name" required autofocus>
                    </label>
                    <label class="field">
                        <span><?= e(t('birthday', $lang)) ?></span>
                        <input type="text" name="birthday" maxlength="20" placeholder="<?= e(t('birthday_ph', $lang)) ?>">
                    </label>
                    <button class="btn btn--primary" type="submit"><?= e(t('join', $lang)) ?></button>
                </form>

            <?php elseif ($member):
                $points = (int) $member['points'];
                $level  = member_level($points);
                $silver = (int) setting('level_silver');
                $gold   = (int) setting('level_gold');
                $next   = $level === 'bronze' ? $silver : ($level === 'silver' ? $gold : null);
                $prev   = $level === 'silver' ? $silver : 0;
                $pct    = $next ? max(4, min(100, (int) round(($points - $prev) / max(1, $next - $prev) * 100))) : 100;
            ?>
                <div class="member-card member-card--<?= $level ?>">
                    <div class="member-card__top">
                        <span class="brand__mark" aria-hidden="true">P</span>
                        <span class="member-card__level"><?= e(LEVELS[$level][$lang]) ?></span>
                    </div>
                    <div class="member-card__name"><?= e($member['name']) ?></div>
                    <div class="member-card__phone" dir="ltr"><?= e(num($member['phone'], $lang)) ?></div>
                    <div class="member-card__points">
                        <strong><?= num($points, $lang) ?></strong>
                        <span><?= e(t('points', $lang)) ?></span>
                    </div>
                    <div class="member-card__bar"><span style="width: <?= $pct ?>%"></span></div>
                    <?php if ($next): ?>
                        <div class="member-card__next">
                            <?= $lang === 'fa'
                                ? e(num($next - $points, $lang) . ' امتیاز تا سطح ' . LEVELS[$level === 'bronze' ? 'silver' : 'gold']['fa'])
                                : e(($next - $points) . ' points to ' . LEVELS[$level === 'bronze' ? 'silver' : 'gold']['en']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="member-card__since"><?= e(t('member_since', $lang)) ?>: <?= e(num(substr($member['created_at'], 0, 10), $lang)) ?></div>
                </div>

                <div class="club__links">
                    <a class="link-btn" href="index.php?lang=<?= e($lang) ?>"><?= e(t('back_to_menu', $lang)) ?></a>
                    <form method="post" action="<?= e($self) ?>"><?= csrf_field() ?><button class="link-btn" name="action" value="reset"><?= e(t('sign_out', $lang)) ?></button></form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
