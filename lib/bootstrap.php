<?php
/**
 * Loads everything a page needs. Every public page and admin page starts with:
 *   require __DIR__ . '/lib/bootstrap.php';
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require ROOT . '/config.php';
require ROOT . '/includes/functions.php';
require __DIR__ . '/db.php';
require __DIR__ . '/security.php';
require __DIR__ . '/sms.php';
require __DIR__ . '/club.php';
