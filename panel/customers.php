<?php
require_once __DIR__ . '/../app/init.php';
if (!is_admin()) { flash_set('e', 'دسترسی فقط برای مدیر.'); redirect('index.php'); }
$pageTitle = 'مشتریان';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id !== (int)current_user()['id']) {
        q("UPDATE users SET status = 1 - status WHERE id = ? AND role = 'customer'", [$id]);
        flash_set('s', 'وضعیت حساب تغییر کرد.');
    }
    redirect('customers.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resetpass') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $pass = (string)($_POST['pass'] ?? '');
    if (strlen($pass) < 6) flash_set('e', 'رمز حداقل ۶ کاراکتر.');
    else {
        q("UPDATE users SET password = ? WHERE id = ? AND role = 'customer'", [password_hash($pass, PASSWORD_DEFAULT), $id]);
        flash_set('s', 'رمز عبور بازنشانی شد.');
    }
    redirect('customers.php');
}

$fq = trim($_GET['q'] ?? '');
$where = "role = 'customer'";
$params = [];
if ($fq !== '') { $where .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)'; $params = ["%$fq%", "%$fq%", "%$fq%"]; }
$per = 20;
$page = max(1, (int)($_GET['p'] ?? 1));
$total = (int)qv("SELECT COUNT(*) FROM users WHERE $where", $params);
$rows = q("SELECT u.*, (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
           (SELECT COALESCE(SUM(o.total),0) FROM orders o WHERE o.user_id = u.id AND o.status != 'cancelled') AS spent
           FROM users u WHERE $where ORDER BY u.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
$pages = max(1, (int)ceil($total / $per));

include __DIR__ . '/inc/header.php';
?>
<div class="p-box">
    <form class="p-filter" method="get">
        <input name="q" placeholder="نام، موبایل یا ایمیل…" value="<?= e($fq) ?>">
        <button class="btn btn-primary btn-sm" type="submit">جستجو</button>
    </form>
    <table class="p-table">
        <thead><tr><th>نام</th><th>موبایل</th><th>ایمیل</th><th>سفارش‌ها</th><th>مجموع خرید</th><th>عضویت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="<?= (int)$r['status'] ? '' : 'row-muted' ?>">
                <td><b><?= e($r['name']) ?></b></td>
                <td dir="ltr"><?= e($r['phone'] ?: '—') ?></td>
                <td dir="ltr"><?= e($r['email'] ?: '—') ?></td>
                <td><?= fa_num($r['orders_count']) ?></td>
                <td><?= price($r['spent']) ?></td>
                <td><small><?= jdate_human($r['created_at']) ?></small></td>
                <td><span class="tag tag-<?= (int)$r['status'] ? 'g' : 'r' ?>"><?= (int)$r['status'] ? 'فعال' : 'غیرفعال' ?></span></td>
                <td>
                    <form method="post" style="display:inline"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-ghost"><?= (int)$r['status'] ? 'غیرفعال‌سازی' : 'فعال‌سازی' ?></button>
                    </form>
                    <form method="post" style="display:inline" class="inline-form"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="resetpass">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <input type="password" name="pass" placeholder="رمز جدید" style="width:110px">
                        <button class="btn btn-sm btn-ghost">بازنشانی رمز</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <nav class="pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'on' : '' ?>" href="customers.php?q=<?= urlencode($fq) ?>&p=<?= $i ?>"><?= fa_num($i) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
