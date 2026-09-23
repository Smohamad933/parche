<?php
require_once __DIR__ . '/app/init.php';
if (current_user()) redirect('account.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $u = auth_attempt($_POST['login'] ?? '', $_POST['password'] ?? '');
    if ($u === 'disabled') {
        flash_set('e', 'حساب شما غیرفعال شده است؛ با پشتیبانی تماس بگیرید.');
    } elseif ($u) {
        auth_login($u);
        flash_set('s', 'خوش آمدید ' . $u['name'] . '!');
        $next = $_POST['next'] ?? 'account.php';
        redirect($next && strpos($next, '//') === false ? $next : 'account.php');
    } else {
        flash_set('e', 'ایمیل/موبایل یا رمز عبور اشتباه است.');
    }
}
$pageTitle = 'ورود';
include __DIR__ . '/inc/header.php';
?>
<div class="auth-box">
    <h1>ورود به حساب</h1>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($_GET['next'] ?? 'account.php') ?>">
        <label>ایمیل یا شماره موبایل
            <input name="login" required dir="ltr" placeholder="you@example.com">
        </label>
        <label>رمز عبور
            <input name="password" type="password" required>
        </label>
        <button class="btn btn-primary btn-block" type="submit">ورود</button>
    </form>
    <p class="muted">حساب ندارید؟ <a href="register.php">ثبت‌نام کنید</a></p>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
