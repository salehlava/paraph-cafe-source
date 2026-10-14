<?php
define('ADMIN_PUBLIC', true);
require __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    admin_sign_out();
    session_regenerate_id(true);
}
redirect(ADMIN_URL . 'login.php');
