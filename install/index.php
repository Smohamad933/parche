<?php
/**
 * نصب‌کننده فروشگاه پارچه — MySQL (IIS) یا SQLite
 */
define('PARCHE_SKIP_INSTALL_CHECK', true);
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/schema.php';

$lockFile = __DIR__ . '/.locked';
$locked = file_exists($lockFile);

function req_ok() {
    return [
        'php' => version_compare(PHP_VERSION, '7.4.0', '>=') ? [true, PHP_VERSION] : [false, PHP_VERSION . ' (نیاز به 7.4+)'],
        'pdo_mysql' => [extension_loaded('pdo_mysql'), extension_loaded('pdo_mysql') ? 'موجود' : 'افزونه pdo_mysql فعال نیست'],
        'pdo_sqlite' => [extension_loaded('pdo_sqlite'), extension_loaded('pdo_sqlite') ? 'موجود' : 'غیرفعال (فقط برای دمو محلی لازم است)'],
        'mbstring' => [extension_loaded('mbstring'), extension_loaded('mbstring') ? 'موجود' : 'پیشنهاد می‌شود فعال شود'],
        'uploads' => [is_writable(__DIR__ . '/../uploads') ?: @mkdir(__DIR__ . '/../uploads', 0775, true) ?: false, 'پوشه uploads باید قابل نوشتن باشد'],
        'data' => [is_writable(__DIR__ . '/../data') ?: @mkdir(__DIR__ . '/../data', 0775, true) ?: false, 'پوشه data باید قابل نوشتن باشد'],
        'config' => [is_writable(__DIR__ . '/../app'), 'پوشه app باید برای ساخت فایل config قابل نوشتن باشد'],
    ];
}

$step = $_POST['step'] ?? 'form';
$errors = [];
$done = false;

if (!$locked && $step === 'run' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $driver = ($_POST['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
    $host = trim($_POST['host'] ?? '127.0.0.1');
    $port = trim($_POST['port'] ?? '3306');
    $name = trim($_POST['name'] ?? 'parche');
    $user = trim($_POST['user'] ?? '');
    $pass = (string)($_POST['pass'] ?? '');
    $sqlitePath = trim($_POST['sqlite_path'] ?? (__DIR__ . '/../data/parche.sqlite'));
    $adminName = trim($_POST['admin_name'] ?? 'مدیر سایت');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $seed = !empty($_POST['seed']);

    try {
        /* اتصال */
        if ($driver === 'mysql') {
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            /* ساخت دیتابیس در صورت نبود */
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");
        } else {
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("PRAGMA foreign_keys = ON");
        }

        /* جداول */
        schema_install($pdo, $driver);

        /* داده دمو */
        if ($seed) seed_demo($pdo, $driver);

        /* حساب مدیر */
        if ($adminPass && strlen($adminPass) >= 6) {
            $pdo->prepare("INSERT INTO users (name, phone, email, password, role, status, created_at) VALUES (?,?,?,?, 'admin', 1, ?)")
                ->execute([$adminName, en_num(trim($_POST['admin_phone'] ?? '')), $adminEmail ?: null, password_hash($adminPass, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        }

        /* نوشتن config */
        $config = [
            'driver' => $driver,
            'host' => $host, 'port' => $port, 'name' => $name, 'user' => $user, 'pass' => $pass,
            'sqlite_path' => $sqlitePath,
        ];
        $php = "<?php\n/* ساخته‌شده توسط نصب‌کننده در " . date('Y-m-d H:i:s') . " */\nreturn " . var_export($config, true) . ";\n";
        file_put_contents(__DIR__ . '/../app/config.php', $php);

        /* قفل نصب‌کننده */
        file_put_contents($lockFile, date('Y-m-d H:i:s'));
        $done = true;
    } catch (Throwable $ex) {
        $errors[] = 'خطا در نصب: ' . $ex->getMessage();
    }
}

$checks = req_ok();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب فروشگاه پارچه</title>
<style>
    body{font-family:Vazirmatn,Tahoma,'Segoe UI',sans-serif;background:#f4f1ea;color:#333;margin:0;padding:40px 16px;line-height:1.9}
    .box{max-width:760px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.08);padding:36px}
    h1{margin:0 0 6px;font-size:26px;color:#1e3a5f}
    .sub{color:#888;margin-bottom:24px}
    table{width:100%;border-collapse:collapse;margin:14px 0}
    td,th{padding:9px 12px;border-bottom:1px solid #eee;text-align:right}
    .ok{color:#188a42;font-weight:bold}.bad{color:#c0392b;font-weight:bold}
    label{display:block;margin:14px 0 4px;font-weight:bold;font-size:14px}
    input,select{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:10px;font:inherit;box-sizing:border-box;background:#fafafa}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .btn{display:inline-block;background:#1e6f5c;color:#fff;border:0;border-radius:12px;padding:13px 34px;font:inherit;font-size:16px;font-weight:bold;cursor:pointer;margin-top:22px}
    .btn:hover{background:#155243}
    .alert{background:#fdecea;color:#b03a2e;border-radius:10px;padding:12px 16px;margin:10px 0}
    .note{background:#eef5f2;border-radius:10px;padding:12px 16px;margin:10px 0;font-size:14px;color:#155243}
    .sec{border-top:2px dashed #eee;margin-top:26px;padding-top:18px}
    a{color:#1e6f5c}
</style>
</head>
<body>
<div class="box">
    <h1>🧵 نصب فروشگاه پارچه</h1>
    <div class="sub">پیش از شروع، دیتابیس و حساب مدیر را پیکربندی کنید.</div>

    <?php foreach (flash_get() as $f): ?>
        <div class="alert"><?= e($f['m']) ?></div>
    <?php endforeach; ?>

    <?php if ($locked && !$done): ?>
        <div class="note">🔒 نصب‌کننده قفل شده است. برای نصب مجدد، فایل <code>install/.locked</code> را حذف کنید.</div>
        <a class="btn" href="../index.php">رفتن به فروشگاه</a>
        <a class="btn" style="background:#888" href="../panel/login.php">ورود به پنل مدیریت</a>
    <?php elseif ($done): ?>
        <div class="note">✅ نصب با موفقیت انجام شد! فایل پیکربندی در <code>app/config.php</code> ذخیره و نصب‌کننده قفل شد.</div>
        <h3 style="margin-top:24px">اطلاعات ورود پنل مدیریت (فقط با لینک مستقیم):</h3>
        <p>آدرس پنل: <code><?= e(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/install')) ?>/panel/login.php</code></p>
        <p>نام کاربری: <code><?= e($adminEmail ?: 'admin@parche.local') ?></code> — رمز همان رمزی است که وارد کردید.</p>
        <a class="btn" href="../index.php">رفتن به فروشگاه</a>
        <a class="btn" style="background:#888" href="../panel/login.php">ورود به پنل مدیریت</a>
    <?php else: ?>
        <?php foreach ($errors as $er): ?><div class="alert"><?= e($er) ?></div><?php endforeach; ?>

        <h3>۱) بررسی پیش‌نیازها</h3>
        <table>
            <?php foreach ($checks as $k => $c): ?>
            <tr><th><?= ['php'=>'نسخه PHP','pdo_mysql'=>'افزونه MySQL (PDO)','pdo_sqlite'=>'افزونه SQLite (PDO)','mbstring'=>'افزونه mbstring','uploads'=>'قابل نوشتن بودن uploads','data'=>'قابل نوشتن بودن data','config'=>'قابل نوشتن بودن app'][$k] ?></th>
                <td class="<?= $c[0] ? 'ok' : 'bad' ?>"><?= $c[0] ? '✔ ' : '✖ ' ?><?= e($c[1]) ?></td></tr>
            <?php endforeach; ?>
        </table>

        <form method="post">
            <input type="hidden" name="step" value="run">
            <?= csrf_field() ?>
            <div class="sec">
            <h3>۲) دیتابیس</h3>
            <label>نوع دیتابیس</label>
            <select name="driver" id="driver" onchange="document.getElementById('mysqlbox').style.display=this.value==='mysql'?'block':'none';document.getElementById('sqlitebox').style.display=this.value==='sqlite'?'block':'none'">
                <option value="mysql" selected>MySQL (پیشنهادی برای IIS)</option>
                <option value="sqlite">SQLite (فقط تست محلی)</option>
            </select>
            <div id="mysqlbox">
                <div class="row">
                    <div><label>هاست</label><input name="host" value="127.0.0.1"></div>
                    <div><label>پورت</label><input name="port" value="3306"></div>
                </div>
                <div class="row">
                    <div><label>نام دیتابیس (در نبود، ساخته می‌شود)</label><input name="name" value="parche"></div>
                    <div><label>نام کاربری MySQL</label><input name="user" value="root"></div>
                </div>
                <label>رمز عبور MySQL</label><input name="pass" type="password">
            </div>
            <div id="sqlitebox" style="display:none">
                <label>مسیر فایل دیتابیس</label>
                <input name="sqlite_path" value="<?= e(__DIR__ . '/../data/parche.sqlite') ?>">
                <div class="note">SQLite فقط برای تست محلی است؛ روی IIS حتماً MySQL را انتخاب کنید.</div>
            </div>
            </div>

            <div class="sec">
            <h3>۳) حساب مدیر پنل</h3>
            <div class="row">
                <div><label>نام مدیر</label><input name="admin_name" value="مدیر سایت"></div>
                <div><label>موبایل</label><input name="admin_phone" value="09120000000"></div>
            </div>
            <label>ایمیل مدیر (نام کاربری ورود به پنل)</label><input name="admin_email" value="admin@parche.local" dir="ltr">
            <label>رمز عبور مدیر (حداقل ۶ کاراکتر)</label><input name="admin_pass" type="password" value="admin123">
            </div>

            <div class="sec">
            <label style="display:flex;gap:8px;align-items:center;font-weight:normal">
                <input type="checkbox" name="seed" value="1" checked style="width:auto">
                نصب داده‌های نمونه (دسته‌بندی‌ها، محصولات دمو، سفارش نمونه، کد تخفیف …)
            </label>
            </div>

            <button class="btn" type="submit">🚀 شروع نصب</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
