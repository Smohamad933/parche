<?php
require_once __DIR__ . '/../app/init.php';
if (!is_admin()) { flash_set('e', 'دسترسی فقط برای مدیر.'); redirect('index.php'); }
$pageTitle = 'تنظیمات';
$uid = current_user()['id'];

$settingKeys = ['site_name', 'site_tagline', 'hero_title', 'hero_subtitle', 'phone', 'mobile', 'email', 'address', 'instagram', 'telegram', 'whatsapp', 'shipping_flat', 'free_shipping_min', 'about_text', 'footer_note', 'currency', 'font_family', 'font_scale'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_verify();
    foreach ($settingKeys as $k) {
        if (!isset($_POST[$k])) continue;
        $v = trim($_POST[$k]);
        q("DELETE FROM settings WHERE skey = ?", [$k]);
        q("INSERT INTO settings (skey, svalue) VALUES (?,?)", [$k, $v]);
    }
    flash_set('s', 'تنظیمات ذخیره شد.');
    redirect('settings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chpass') {
    csrf_verify();
    $cur = qv("SELECT password FROM users WHERE id = ?", [$uid]);
    $new = (string)($_POST['new_password'] ?? '');
    if (!password_verify($_POST['old_password'] ?? '', $cur)) flash_set('e', 'رمز فعلی اشتباه است.');
    elseif (strlen($new) < 6) flash_set('e', 'رمز جدید حداقل ۶ کاراکتر.');
    else {
        q("UPDATE users SET password = ? WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $uid]);
        flash_set('s', 'رمز عبور مدیر تغییر کرد.');
    }
    redirect('settings.php');
}

include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <h3><?= icon('settings') ?> تنظیمات فروشگاه</h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <div class="row2">
                <label>نام سایت<input name="site_name" value="<?= e(setting('site_name')) ?>"></label>
                <label>شعار<input name="site_tagline" value="<?= e(setting('site_tagline')) ?>"></label>
            </div>
            <div class="row2">
                <label>تیتر صفحه اصلی<input name="hero_title" value="<?= e(setting('hero_title')) ?>"></label>
                <label>زیرتیتر<input name="hero_subtitle" value="<?= e(setting('hero_subtitle')) ?>"></label>
            </div>
            <div class="row3">
                <label>تلفن<input name="phone" value="<?= e(setting('phone')) ?>"></label>
                <label>موبایل<input name="mobile" value="<?= e(setting('mobile')) ?>"></label>
                <label>ایمیل<input name="email" dir="ltr" value="<?= e(setting('email')) ?>"></label>
            </div>
            <label>آدرس<input name="address" value="<?= e(setting('address')) ?>"></label>
            <div class="row3">
                <label>اینستاگرام<input name="instagram" dir="ltr" value="<?= e(setting('instagram')) ?>"></label>
                <label>تلگرام<input name="telegram" dir="ltr" value="<?= e(setting('telegram')) ?>"></label>
                <label>واتساپ<input name="whatsapp" dir="ltr" value="<?= e(setting('whatsapp')) ?>"></label>
            </div>
            <div class="row2">
                <label>هزینه ارسال ثابت (تومان)<input name="shipping_flat" dir="ltr" value="<?= e(setting('shipping_flat')) ?>"></label>
                <label>سقف ارسال رایگان (تومان)<input name="free_shipping_min" dir="ltr" value="<?= e(setting('free_shipping_min')) ?>"></label>
            </div>
            <label>متن درباره ما<textarea name="about_text" rows="4"><?= e(setting('about_text')) ?></textarea></label>
            <label>متن فوتر<input name="footer_note" value="<?= e(setting('footer_note')) ?>"></label>
            <div class="row2">
                <label>فونت کل سایت
                    <select name="font_family">
                        <?php foreach (available_fonts() as $fk => $fv): ?>
                            <option value="<?= e($fk) ?>" <?= setting('font_family', 'vazirmatn') === $fk ? 'selected' : '' ?>><?= e($fv['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>اندازه فونت
                    <select name="font_scale">
                        <option value="0.9" <?= setting('font_scale', '1') === '0.9' ? 'selected' : '' ?>>کوچک‌تر</option>
                        <option value="1" <?= setting('font_scale', '1') === '1' ? 'selected' : '' ?>>معمولی</option>
                        <option value="1.1" <?= setting('font_scale', '1') === '1.1' ? 'selected' : '' ?>>بزرگ‌تر</option>
                    </select>
                </label>
            </div>
            <button class="btn btn-primary btn-lg" type="submit"><?= icon('save') ?> ذخیره تنظیمات</button>
        </form>
    </div>
    <div>
        <div class="p-box">
            <h3><?= icon('key-round') ?> تغییر رمز مدیر</h3>
            <form method="post" class="p-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="chpass">
                <label>رمز فعلی<input name="old_password" type="password" required></label>
                <label>رمز جدید<input name="new_password" type="password" required minlength="6"></label>
                <button class="btn btn-primary" type="submit">تغییر رمز</button>
            </form>
        </div>
        <div class="p-box">
            <h3><?= icon('shield-check') ?> امنیت پنل</h3>
            <ul class="security-tips">
                <li>پنل هیچ لینکی در سایت عمومی ندارد و فقط با آدرس مستقیم <code dir="ltr">/panel/login.php</code> باز می‌شود.</li>
                <li>برای امنیت بیشتر می‌توانید پوشه <code dir="ltr">panel</code> را به یک نام مخفی تغییر دهید (مثلاً <code dir="ltr">pnl-k3x9</code>)؛ چون همه لینک‌های داخل پنل نسبی هستند، پنل همچنان کار می‌کند.</li>
                <li>روی IIS پوشه‌های <code dir="ltr">app</code> و <code dir="ltr">data</code> از طریق web.config مسدود شده‌اند.</li>
                <li>پس از راه‌اندازی، نصب‌کننده به‌صورت خودکار قفل می‌شود (<code dir="ltr">install/.locked</code>).</li>
            </ul>
        </div>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
