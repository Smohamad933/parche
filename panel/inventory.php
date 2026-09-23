<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'انبار و موجودی';
$isAdmin = is_admin();
$uid = current_user()['id'];
$scope = $isAdmin ? '' : ' AND seller_id = ' . $uid;

/* ثبت ورود/خروج/اصلاح موجودی */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'adjust') {
    csrf_verify();
    $pid = (int)($_POST['product_id'] ?? 0);
    $type = in_array($_POST['type'] ?? '', ['in', 'out'], true) ? $_POST['type'] : 'in';
    $qty = (float)en_num($_POST['qty'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    $p = q1("SELECT * FROM products WHERE id = ? AND (1=1 $scope)", [$pid]);
    if (!$p) flash_set('e', 'محصول یافت نشد.');
    elseif ($qty <= 0) flash_set('e', 'مقدار باید بزرگ‌تر از صفر باشد.');
    elseif ($type === 'out' && $qty > (float)$p['stock']) flash_set('e', 'موجودی فعلی کمتر از مقدار خروج است.');
    else {
        $change = $type === 'in' ? $qty : -$qty;
        $after = (float)$p['stock'] + $change;
        q("UPDATE products SET stock = ? WHERE id = ?", [$after, $pid]);
        q("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, stock_after, created_at) VALUES (?,?,?,?,?,?,?)",
          [$pid, $uid, $change, $type, $note ?: ($type === 'in' ? 'ورود کالا به انبار' : 'خروج کالا از انبار'), $after, now()]);
        flash_set('s', 'موجودی «' . $p['name'] . '» به‌روزرسانی شد: ' . fa_num(rtrim(rtrim((string)$after, '0'), '.')) . ' متر');
    }
    redirect('inventory.php' . ($pid ? '?product=' . $pid : ''));
}

$selProduct = !empty($_GET['product']) ? q1("SELECT * FROM products WHERE id = ? AND (1=1 $scope)", [(int)$_GET['product']]) : null;

/* لیست محصولات با موجودی */
$rows = q("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
           WHERE 1=1 $scope ORDER BY (p.stock <= p.low_stock) DESC, p.stock ASC, p.id DESC LIMIT 100")->fetchAll();

/* آخرین لاگ‌ها */
$logs = q("SELECT l.*, p.name AS pname, u.name AS uname FROM inventory_logs l
           JOIN products p ON p.id = l.product_id LEFT JOIN users u ON u.id = l.user_id
           WHERE 1=1 " . ($isAdmin ? '' : ' AND p.seller_id = ' . $uid) . "
           ORDER BY l.id DESC LIMIT 40")->fetchAll();

include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <h3><?= $selProduct ? '🔄 ثبت حرکت برای: ' . e($selProduct['name']) : '🔄 ثبت ورود/خروج انبار' ?></h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="adjust">
            <label>محصول
                <select name="product_id" required>
                    <option value="">— انتخاب محصول —</option>
                    <?php foreach ($rows as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $selProduct && (int)$selProduct['id'] === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (موجودی: <?= fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="row2">
                <label>نوع حرکت
                    <select name="type">
                        <option value="in">➕ ورود به انبار</option>
                        <option value="out">➖ خروج از انبار</option>
                    </select>
                </label>
                <label>مقدار (متر)<input name="qty" dir="ltr" required placeholder="مثلاً 50"></label>
            </div>
            <label>توضیحات<input name="note" placeholder="مثلاً رسید خرید از نساجی البرز، پالت #۱۲۳"></label>
            <button class="btn btn-primary" type="submit">💾 ثبت حرکت انبار</button>
        </form>
        <div class="p-note">📦 هر سفارش مشتری و هر لغو، خودکار در این دفتر ثبت می‌شود (نوع «order» و «return»).</div>
    </div>

    <div class="p-box">
        <h3>موجودی محصولات (کم‌موجود در بالا)</h3>
        <table class="p-table">
            <thead><tr><th>محصول</th><th>دسته</th><th>موجودی</th><th>حد هشدار</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $p): ?>
                <tr>
                    <td><b><?= e($p['name']) ?></b></td>
                    <td><?= e($p['cat_name'] ?: '—') ?></td>
                    <td><?= fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) ?></td>
                    <td><?= fa_num($p['low_stock']) ?></td>
                    <td>
                        <?php if ((float)$p['stock'] <= 0): ?><span class="tag tag-r">ناموجود</span>
                        <?php elseif ((float)$p['stock'] <= (float)$p['low_stock']): ?><span class="tag tag-o">کمبود</span>
                        <?php else: ?><span class="tag tag-g">مناسب</span><?php endif; ?>
                    </td>
                    <td><a class="btn btn-sm btn-primary" href="inventory.php?product=<?= (int)$p['id'] ?>">حرکت</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="p-box">
    <h3>دفتر حرکات انبار</h3>
    <table class="p-table">
        <thead><tr><th>تاریخ</th><th>محصول</th><th>تغییر</th><th>نوع</th><th>باقی‌مانده</th><th>توضیح</th><th>کاربر</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td><small><?= jdate_human($l['created_at']) ?></small></td>
                <td><?= e($l['pname']) ?></td>
                <td class="<?= (float)$l['change_qty'] >= 0 ? 'txt-ok' : 'txt-danger' ?>"><b><?= (float)$l['change_qty'] >= 0 ? '+' : '' ?><?= fa_num(rtrim(rtrim((string)$l['change_qty'], '0'), '.')) ?></b></td>
                <td><?= ['in'=>'ورود','out'=>'خروج','adjust'=>'اصلاح','order'=>'سفارش','return'=>'مرجوعی'][$l['type']] ?? $l['type'] ?></td>
                <td><?= fa_num(rtrim(rtrim((string)$l['stock_after'], '0'), '.')) ?></td>
                <td><small><?= e($l['note']) ?></small></td>
                <td><small><?= e($l['uname'] ?: '—') ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
