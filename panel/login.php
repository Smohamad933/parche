<?php
/** ورود به پنل مدیریت/فروشندگان — فقط از طریق لینک مستقیم */
define('PARCHE_SKIP_INSTALL_CHECK', false);
require_once __DIR__ . '/../app/init.php';

/* خروج */
if (isset($_GET['logout'])) {
    auth_logout();
    flash_set('s', 'از پنل خارج شدید.');
    redirect('login.php');
}

if (current_user() && is_staff()) redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $u = auth_attempt($_POST['login'] ?? '', $_POST['password'] ?? '');
    if ($u === 'disabled') {
        flash_set('e', 'این حساب غیرفعال است.');
    } elseif ($u && in_array($u['role'], ['admin', 'seller'], true)) {
        auth_login($u);
        redirect('index.php');
    } elseif ($u) {
        flash_set('e', 'این حساب دسترسی پنل ندارد.');
    } else {
        flash_set('e', 'اطلاعات ورود اشتباه است.');
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ورود به پنل — <?= e(setting('site_name', 'پارچه‌سرا')) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231e6f5c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx=%226%22 cy=%226%22 r=%223%22 /%3E %3Cpath d=%22M8.12 8.12 12 12%22 /%3E %3Cpath d=%22M20 4 8.12 15.88%22 /%3E %3Ccircle cx=%226%22 cy=%2218%22 r=%223%22 /%3E %3Cpath d=%22M14.8 14.8 20 20%22 /%3E%3C/svg%3E">
<?= font_head_tags() ?>
<link rel="stylesheet" href="../assets/css/panel.css">
</head>
<body class="login-page">
<div class="login-box">
    <h1><?= icon('scissors') ?> پنل <?= e(setting('site_name', 'پارچه‌سرا')) ?></h1>
    <p class="muted">ورود مدیران و فروشندگان</p>
    <?php foreach (flash_get() as $f): ?>
        <div class="p-alert p-alert-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label>ایمیل یا موبایل
            <input name="login" required dir="ltr" autofocus>
        </label>
        <label>رمز عبور
            <input name="password" type="password" required>
        </label>
        <button class="btn btn-primary btn-block" type="submit">ورود به پنل</button>
    </form>
    <a class="muted back" href="../index.php"><?= icon('arrow-right') ?> بازگشت به سایت</a>
</div>
</body>
</html>
