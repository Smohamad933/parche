<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'مدیریت نظرات';
$isAdmin = is_admin();
$uid = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $scopeJoin = $isAdmin ? '' : ' AND p.seller_id = ' . $uid;
    $r = q1("SELECT r.* FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.id = ? $scopeJoin", [$id]);
    if ($r) {
        if ($_POST['action'] === 'approve') { q("UPDATE reviews SET status='approved' WHERE id = ?", [$id]); flash_set('s', 'نظر تایید و منتشر شد.'); }
        if ($_POST['action'] === 'reject') { q("UPDATE reviews SET status='rejected' WHERE id = ?", [$id]); flash_set('s', 'نظر رد شد.'); }
        if ($_POST['action'] === 'delete') { q("DELETE FROM reviews WHERE id = ?", [$id]); flash_set('s', 'نظر حذف شد.'); }
    }
    redirect('reviews.php');
}

$rows = q("SELECT r.*, p.name AS pname FROM reviews r JOIN products p ON p.id = r.product_id
           WHERE 1=1 " . ($isAdmin ? '' : ' AND p.seller_id = ' . $uid) . "
           ORDER BY (r.status='pending') DESC, r.id DESC LIMIT 100")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<div class="p-box">
    <h3>⭐ نظرات مشتریان</h3>
    <table class="p-table">
        <thead><tr><th>محصول</th><th>کاربر</th><th>امتیاز</th><th>متن</th><th>تاریخ</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr class="<?= $r['status'] === 'pending' ? 'row-warn' : '' ?>">
                <td><?= e($r['pname']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= stars($r['rating']) ?></td>
                <td class="td-wide"><?= e(mb_substr($r['comment'], 0, 120)) ?></td>
                <td><small><?= jdate_human($r['created_at']) ?></small></td>
                <td><span class="tag tag-<?= ['pending' => 'o', 'approved' => 'g', 'rejected' => 'r'][$r['status']] ?>"><?= ['pending' => 'در انتظار', 'approved' => 'منتشرشده', 'rejected' => 'ردشده'][$r['status']] ?></span></td>
                <td>
                    <?php if ($r['status'] !== 'approved'): ?>
                    <form method="post" style="display:inline"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-primary">تایید</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($r['status'] !== 'rejected'): ?>
                    <form method="post" style="display:inline"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-ghost">رد</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('حذف نظر؟')"><?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-danger">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
