<?php
require_once __DIR__ . '/../app/init.php';
if (!is_admin()) { flash_set('e', 'دسترسی فقط برای مدیر.'); redirect('index.php'); }
$pageTitle = 'فروشندگان';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $shop = trim($_POST['shop_name'] ?? '');
    $phone = en_num(trim($_POST['phone'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    if (mb_strlen($name) < 3 || strlen($pass) < 6) {
        flash_set('e', 'نام و رمز (حداقل ۶ کاراکتر) لازم است.');
    } elseif ($email && q1("SELECT id FROM users WHERE email = ?", [$email])) {
        flash_set('e', 'این ایمیل قبلاً ثبت شده.');
    } else {
        q("INSERT INTO users (name, phone, email, password, role, shop_name, status, created_at) VALUES (?,?,?,?, 'seller', ?, 1, ?)",
          [$name, $phone ?: null, $email ?: null, password_hash($pass, PASSWORD_DEFAULT), $shop ?: null, now()]);
        flash_set('s', 'فروشنده «' . $shop . '» اضافه شد. می‌تواند با ایمیل/موبایل در پنل ورود کند.');
    }
    redirect('sellers.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    csrf_verify();
    q("UPDATE users SET status = 1 - status WHERE id = ? AND role = 'seller'", [(int)($_POST['id'] ?? 0)]);
    redirect('sellers.php');
}

$rows = q("SELECT u.*, (SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) AS products_count,
           (SELECT COUNT(DISTINCT oi.order_id) FROM order_items oi WHERE oi.seller_id = u.id) AS orders_count
           FROM users u WHERE role = 'seller' ORDER BY u.id")->fetchAll();

include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <h3>➕ افزودن فروشنده</h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="row2">
                <label>نام مسئول *<input name="name" required></label>
                <label>نام فروشگاه *<input name="shop_name" required placeholder="مثلاً پارچه‌فروشی نور"></label>
            </div>
            <div class="row2">
                <label>موبایل<input name="phone" dir="ltr"></label>
                <label>ایمیل (نام کاربری ورود)<input name="email" type="email" dir="ltr" required></label>
            </div>
            <label>رمز عبور (حداقل ۶ کاراکتر) *<input name="password" required></label>
            <button class="btn btn-primary" type="submit">افزودن فروشنده</button>
        </form>
        <div class="p-note">🏬 فروشنده پس از ورود به پنل، فقط محصولات، سفارش‌ها و فاکتورهای خودش را می‌بیند.</div>
    </div>
    <div class="p-box">
        <h3>فروشندگان</h3>
        <table class="p-table">
            <thead><tr><th>فروشگاه</th><th>مسئول</th><th>تماس</th><th>محصولات</th><th>سفارش‌ها</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr class="<?= (int)$r['status'] ? '' : 'row-muted' ?>">
                    <td><b><?= e($r['shop_name'] ?: '—') ?></b></td>
                    <td><?= e($r['name']) ?></td>
                    <td dir="ltr"><small><?= e($r['email'] ?: $r['phone']) ?></small></td>
                    <td><?= fa_num($r['products_count']) ?></td>
                    <td><?= fa_num($r['orders_count']) ?></td>
                    <td><span class="tag tag-<?= (int)$r['status'] ? 'g' : 'r' ?>"><?= (int)$r['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <form method="post"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-sm btn-ghost"><?= (int)$r['status'] ? 'غیرفعال' : 'فعال' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
