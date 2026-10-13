<?php
/**
 * SMS panel API.
 *
 * Usage:  sms_send('09121234567', 'Hello');          // one number
 *         sms_send(['0912...', '0935...'], 'Hello');  // many numbers
 *
 * The provider and credentials are set in Admin → SMS.
 * "log" is a test mode: nothing is sent, messages are only saved in the SMS log.
 */

const SMS_DRIVERS = [
    'log'         => 'حالت تست (بدون ارسال واقعی)',
    'kavenegar'   => 'کاوه‌نگار (Kavenegar)',
    'smsir'       => 'اس‌ام‌اس دات آی‌آر (SMS.ir)',
    'melipayamak' => 'ملی پیامک (Melipayamak)',
    'ippanel'     => 'آی‌پی پنل / فراز اس‌ام‌اس (IPPanel)',
];

/**
 * Send an SMS. Returns ['ok' => bool, 'response' => string].
 * $logMessage: what to save in the SMS log instead of the real text (used to hide codes).
 */
function sms_send($to, string $message, ?string $logMessage = null): array
{
    $to     = array_values(array_unique(array_filter((array) $to)));
    $driver = setting('sms_driver', 'log');
    $key    = (string) setting('sms_api_key');
    $sender = (string) setting('sms_sender');

    if (!$to) {
        return ['ok' => false, 'response' => 'No recipients'];
    }

    $ok = false;
    $response = '';

    try {
        // Providers accept a limited number of recipients per request.
        foreach (array_chunk($to, 100) as $chunk) {
            switch ($driver) {
                case 'kavenegar':
                    // https://kavenegar.com/rest.html
                    $r = http_request('POST', 'https://api.kavenegar.com/v1/' . rawurlencode($key) . '/sms/send.json', [], [
                        'receptor' => implode(',', $chunk),
                        'sender'   => $sender,
                        'message'  => $message,
                    ]);
                    $ok = (int) ($r['json']['return']['status'] ?? 0) === 200;
                    break;

                case 'smsir':
                    // https://app.sms.ir/developer/help/bulk
                    $r = http_request('POST', 'https://api.sms.ir/v1/send/bulk', [
                        'X-API-KEY: ' . $key,
                        'Accept: application/json',
                    ], null, [
                        'lineNumber'  => (int) $sender,
                        'messageText' => $message,
                        'mobiles'     => $chunk,
                    ]);
                    $ok = (int) ($r['json']['status'] ?? 0) === 1;
                    break;

                case 'melipayamak':
                    // https://www.melipayamak.com/api/  (REST: SendSMS)
                    $r = http_request('POST', 'https://rest.payamak-panel.com/api/SendSMS/SendSMS', [], [
                        'username' => (string) setting('sms_username'),
                        'password' => (string) setting('sms_password'),
                        'to'       => implode(',', $chunk),
                        'from'     => $sender,
                        'text'     => $message,
                        'isFlash'  => 'false',
                    ]);
                    $ok = (int) ($r['json']['RetStatus'] ?? 0) === 1;
                    break;

                case 'ippanel':
                    // https://docs.ippanel.com
                    $r = http_request('POST', 'https://api2.ippanel.com/api/v1/sms/send/webservice/single', [
                        'apikey: ' . $key,
                        'Accept: application/json',
                    ], null, [
                        'recipient' => $chunk,
                        'sender'    => $sender,
                        'message'   => $message,
                    ]);
                    $ok = strtoupper((string) ($r['json']['status'] ?? '')) === 'OK';
                    break;

                default: // 'log' — test mode
                    $r = ['status' => 200, 'body' => 'TEST MODE: not sent'];
                    $ok = true;
            }

            $response = 'HTTP ' . $r['status'] . ' ' . substr((string) $r['body'], 0, 500);
            if (!$ok) {
                break;
            }
        }
    } catch (Throwable $ex) {
        $ok = false;
        $response = 'Error: ' . $ex->getMessage();
    }

    db()->prepare('INSERT INTO sms_log (recipients, message, driver, ok, response) VALUES (?, ?, ?, ?, ?)')
        ->execute([implode(',', $to), $logMessage ?? $message, $driver, $ok ? 1 : 0, $response]);

    return ['ok' => $ok, 'response' => $response];
}

/**
 * Minimal HTTP client. $form = urlencoded body, $json = JSON body.
 * Returns ['status' => int, 'body' => string, 'json' => ?array].
 */
function http_request(string $method, string $url, array $headers = [], ?array $form = null, ?array $json = null): array
{
    $body = null;
    if ($json !== null) {
        $body = json_encode($json, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
    } elseif ($form !== null) {
        $body = http_build_query($form);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        // On Windows PHP has no CA bundle by default: trust the Windows certificate store.
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA') && !ini_get('curl.cainfo')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resBody = curl_exec($ch);
        if ($resBody === false) {
            $err = curl_error($ch);
            throw new RuntimeException($err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    } else {
        $ctx = stream_context_create(['http' => [
            'method'        => $method,
            'header'        => implode("\r\n", $headers),
            'content'       => $body ?? '',
            'timeout'       => 20,
            'ignore_errors' => true,
        ]]);
        $resBody = @file_get_contents($url, false, $ctx);
        if ($resBody === false) {
            throw new RuntimeException('Connection failed (is the PHP openssl extension enabled?)');
        }
        $status = preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0] ?? '', $m) ? (int) $m[1] : 0;
    }

    $decoded = json_decode((string) $resBody, true);
    return ['status' => $status, 'body' => (string) $resBody, 'json' => is_array($decoded) ? $decoded : null];
}
