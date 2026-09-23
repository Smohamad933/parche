<?php
require_once __DIR__ . '/app/init.php';
if (current_user()) redirect('account.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $phone = en_num(trim($_POST['phone'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    $errors = [];
    if (mb_strlen($name) < 3) $errors[] = 'نام را کامل وارد کنید.';
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) $errors[] = 'شماره موبایل معتبر نیست.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل معتبر نیست.';
    if (!$email && !$phone) $errors[] = 'حداقل یکی از ایمیل یا موبایل لازم است.';
    if (strlen($pass) < 6) $errors[] = 'رمز عبور حداقل ۶ کاراکتر باشد.';
    if ($pass !== $pass2) $errors[] = 'تکرار رمز عبور مطابقت ندارد.';

    if (!$errors) {
        $r = auth_register($name, $phone, $email, $pass);
        if ($r === true) {
            $u = auth_attempt($email ?: $phone, $pass);
            auth_login($u);
            flash_set('s', 'ثبت‌نام انجام شد؛ خوش آمدید! 🎉');
            redirect('account.php');
        } else {
            $errors[] = $r[1];
        }
    }
    foreach ($errors as $er) flash_set('e', $er);
}
$pageTitle = 'ثبت‌نام';
include __DIR__ . '/inc/header.php';
?>
<div class="auth-box">
    <h1>ساخت حساب کاربری</h1>
    <form method="post">
        <?= csrf_field() ?>
        <label>نام و نام خانوادگی *
            <input name="name" required>
        </label>
        <label>شماره موبایل
            <input name="phone" dir="ltr" placeholder="09121234567">
        </label>
        <label>ایمیل
            <input name="email" type="email" dir="ltr" placeholder="you@example.com">
        </label>
        <label>رمز عبور *
            <input name="password" type="password" required minlength="6">
        </label>
        <label>تکرار رمز عبور *
            <input name="password2" type="password" required>
        </label>
        <button class="btn btn-primary btn-block" type="submit">ثبت‌نام</button>
    </form>
    <p class="muted">قبلاً ثبت‌نام کرده‌اید؟ <a href="login.php">ورود</a></p>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
