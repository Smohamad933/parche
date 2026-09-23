<?php
/**
 * بوت‌استرپ برنامه — در ابتدای همه صفحات include می‌شود
 */
define('PARCHE', '1.0');

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Tehran');
if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');

/* سشن */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('PARCHESESS');
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

/* اگر نصب نشده → هدایت به نصب‌کننده (به‌جز خود نصب‌کننده و CLI) */
if (!defined('PARCHE_SKIP_INSTALL_CHECK') && !db_config()) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $inInstall = (strpos(($_SERVER['SCRIPT_NAME'] ?? ''), '/install/') !== false);
    if (!$inInstall && php_sapi_name() !== 'cli') {
        redirect('install/');
    }
}

/* پوشه آپلود قابل نوشتن باشد */
$up = __DIR__ . '/../uploads';
if (!is_dir($up)) @mkdir($up, 0775, true);
