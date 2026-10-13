<?php
/**
 * Customer club: phone numbers, one-time codes, members and levels.
 */

/** Normalise an Iranian mobile number to 09xxxxxxxxx, or null if invalid. */
function normalize_phone(string $p): ?string
{
    $p = preg_replace('/\D+/', '', latin_digits($p));
    if (strpos($p, '0098') === 0) {
        $p = substr($p, 4);
    } elseif (strpos($p, '98') === 0 && strlen($p) === 12) {
        $p = substr($p, 2);
    }
    if (strlen($p) === 10 && $p[0] === '9') {
        $p = '0' . $p;
    }
    return preg_match('/^09\d{9}$/', $p) ? $p : null;
}

function otp_hash(string $phone, string $code): string
{
    return hash_hmac('sha256', $phone . '|' . $code, (string) setting('app_secret'));
}

/**
 * Create and send a one-time code.
 * Returns ['ok' => bool, 'error' => ?string, 'wait' => int, 'test_code' => ?string].
 */
function otp_send(string $phone): array
{
    $row = db()->prepare('SELECT * FROM otp WHERE phone = ?');
    $row->execute([$phone]);
    $row = $row->fetch();

    $wait = $row ? 60 - (time() - (int) $row['last_sent']) : 0;
    if ($wait > 0) {
        return ['ok' => false, 'error' => 'wait', 'wait' => $wait];
    }

    $code = (string) random_int(10000, 99999);
    db()->prepare('INSERT INTO otp (phone, code_hash, expires_at, attempts, last_sent) VALUES (?, ?, ?, 0, ?)
                   ON CONFLICT(phone) DO UPDATE SET code_hash = excluded.code_hash, expires_at = excluded.expires_at, attempts = 0, last_sent = excluded.last_sent')
        ->execute([$phone, otp_hash($phone, $code), time() + 300, time()]);

    $tpl = (string) setting('sms_otp_tpl');
    $res = sms_send($phone, str_replace('{code}', $code, $tpl), str_replace('{code}', '•••••', $tpl));
    if (!$res['ok']) {
        return ['ok' => false, 'error' => 'sms', 'wait' => 0];
    }

    return ['ok' => true, 'error' => null, 'wait' => 0, 'test_code' => setting('sms_driver') === 'log' ? $code : null];
}

/** Check a one-time code. Max 5 attempts, valid for 5 minutes. */
function otp_verify(string $phone, string $code): bool
{
    $stmt = db()->prepare('SELECT * FROM otp WHERE phone = ?');
    $stmt->execute([$phone]);
    $row = $stmt->fetch();

    if (!$row || (int) $row['expires_at'] < time() || (int) $row['attempts'] >= 5) {
        return false;
    }
    if (hash_equals($row['code_hash'], otp_hash($phone, preg_replace('/\D+/', '', latin_digits($code))))) {
        db()->prepare('DELETE FROM otp WHERE phone = ?')->execute([$phone]);
        return true;
    }
    db()->prepare('UPDATE otp SET attempts = attempts + 1 WHERE phone = ?')->execute([$phone]);
    return false;
}

function member_by_phone(string $phone): ?array
{
    $stmt = db()->prepare('SELECT * FROM members WHERE phone = ?');
    $stmt->execute([$phone]);
    return $stmt->fetch() ?: null;
}

function member_level(int $points): string
{
    if ($points >= (int) setting('level_gold')) {
        return 'gold';
    }
    if ($points >= (int) setting('level_silver')) {
        return 'silver';
    }
    return 'bronze';
}

/** Add (or subtract) points and keep a history entry. */
function member_add_points(int $memberId, int $delta, string $reason = ''): void
{
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE members SET points = MAX(0, points + ?) WHERE id = ?')->execute([$delta, $memberId]);
    $pdo->prepare('INSERT INTO points_log (member_id, delta, reason) VALUES (?, ?, ?)')->execute([$memberId, $delta, $reason]);
    $pdo->commit();
}

/** Fill {name}, {points}, {phone} placeholders in an SMS template. */
function sms_template(string $tpl, array $member): string
{
    return strtr($tpl, [
        '{name}'   => $member['name'] ?? '',
        '{points}' => (string) ($member['points'] ?? 0),
        '{phone}'  => $member['phone'] ?? '',
    ]);
}
