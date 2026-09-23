<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'سفارش‌ها';
$isAdmin = is_admin();
$uid = current_user()['id'];
$sid = $isAdmin ? 0 : $uid;

if (!$isAdmin && $sid) {
    $scopeOrders = "id IN (SELECT order_id FROM order_items WHERE seller_id = " . $sid . ")";
} else {
    $scopeOrders = '1=1';
}

/* تغییر وضعیت */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    csrf_verify();
    $oid = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $order = q1("SELECT * FROM orders WHERE id = ? AND $scopeOrders", [$oid]);
    if (!$order || !array_key_exists($newStatus, order_statuses())) {
        flash_set('e', 'درخواست نامعتبر است.');
    } else {
        tx_start();
        try {
            /* لغو → برگشت موجودی */
            if ($newStatus === 'cancelled' && $order['status'] !== 'cancelled') {
                $items = q("SELECT * FROM order_items WHERE order_id = ?" . ($sid ? " AND seller_id = $sid" : ''), [$oid])->fetchAll();
                foreach ($items as $it) {
                    if ($it['product_id']) {
                        q("UPDATE products SET stock = stock + ? WHERE id = ?", [$it['qty'], $it['product_id']]);
                        $after = (float)qv("SELECT stock FROM products WHERE id = ?", [$it['product_id']]);
                        q("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, order_id, stock_after, created_at) VALUES (?,?,?,?,?,?,?,?)",
                          [$it['product_id'], $uid, $it['qty'], 'return', 'برگشت به انبار پس از لغو سفارش', $oid, $after, now()]);
                    }
                }
            }
            /* لغو فاکتورها */
            if ($newStatus === 'cancelled') {
                q("UPDATE invoices SET status='cancelled' WHERE order_id = ?" . ($sid ? " AND seller_id = $sid" : ''), [$oid]);
            }
            /* تحویل → فاکتور پرداخت‌شده */
            if ($newStatus === 'delivered') {
                q("UPDATE invoices SET status='paid', paid_at = ? WHERE order_id = ? AND status != 'cancelled'" . ($sid ? " AND seller_id = $sid" : ''), [now(), $oid]);
            }
            q("UPDATE orders SET status = ?, updated_at = ? WHERE id = ?", [$newStatus, now(), $oid]);
            q("INSERT INTO order_history (order_id, status, note, by_user_id, created_at) VALUES (?,?,?,?,?)",
              [$oid, $newStatus, $note ?: ('تغییر وضعیت به «' . order_status_label($newStatus) . '»'), $uid, now()]);
            /* اطلاع‌رسانی به مشتری */
            if ($order['user_id']) {
                q("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?,?,?,?,0,?)",
                  [$uid, $order['user_id'], 'به‌روزرسانی سفارش ' . $order['code'],
                   "وضعیت سفارش «{$order['code']}» به «" . order_status_label($newStatus) . "» تغییر کرد.\n" . ($note ?: ''), now()]);
            }
            tx_commit();
            flash_set('s', 'وضعیت سفارش به «' . order_status_label($newStatus) . '» تغییر کرد.');
        } catch (Throwable $ex) {
            tx_rollback();
            flash_set('e', 'خطا: ' . $ex->getMessage());
        }
    }
    redirect('orders.php?view=' . $oid);
}

/* لیست */
$fStatus = $_GET['status'] ?? '';
$fq = trim($_GET['q'] ?? '');
$where = $scopeOrders;
$params = [];
if ($fStatus && array_key_exists($fStatus, order_statuses())) { $where .= ' AND status = ?'; $params[] = $fStatus; }
if ($fq !== '') { $where .= ' AND (code LIKE ? OR customer_name LIKE ? OR phone LIKE ?)'; $params[] = "%$fq%"; $params[] = "%$fq%"; $params[] = "%$fq%"; }
$per = 15;
$page = max(1, (int)($_GET['p'] ?? 1));
$total = (int)qv("SELECT COUNT(*) FROM orders WHERE $where", $params);
$rows = q("SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
           FROM orders o WHERE $where ORDER BY o.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
$pages = max(1, (int)ceil($total / $per));

/* نمایش سفارش */
$view = !empty($_GET['view']) ? q1("SELECT * FROM orders WHERE id = ? AND $scopeOrders", [(int)$_GET['view']]) : null;
$viewItems = $view ? q("SELECT * FROM order_items WHERE order_id = ?" . ($sid ? " AND seller_id = $sid" : ''), [$view['id']])->fetchAll() : [];
$viewHist = $view ? q("SELECT h.*, u.name AS uname FROM order_history h LEFT JOIN users u ON u.id = h.by_user_id WHERE order_id = ? ORDER BY h.id", [$view['id']])->fetchAll() : [];
$viewInvs = $view ? q("SELECT * FROM invoices WHERE order_id = ?" . ($sid ? " AND seller_id = $sid" : ''), [$view['id']])->fetchAll() : [];

include __DIR__ . '/inc/header.php';
?>
<?php if ($view): ?>
<div class="p-box">
    <div class="box-head">
        <h3>سفارش <span dir="ltr"><?= e($view['code']) ?></span></h3>
        <a class="btn btn-ghost btn-sm" href="orders.php">← بازگشت به لیست</a>
    </div>
    <div class="cols-2">
        <div>
            <h4>اطلاعات مشتری</h4>
            <table class="p-table kv">
                <tr><td>نام</td><td><b><?= e($view['customer_name']) ?></b></td></tr>
                <tr><td>موبایل</td><td dir="ltr"><?= e($view['phone']) ?></td></tr>
                <tr><td>شهر</td><td><?= e($view['city'] ?: '—') ?></td></tr>
                <tr><td>کد پستی</td><td dir="ltr"><?= e($view['postal_code'] ?: '—') ?></td></tr>
                <tr><td>آدرس</td><td><?= e($view['address']) ?></td></tr>
                <tr><td>توضیح مشتری</td><td><?= e($view['note'] ?: '—') ?></td></tr>
                <tr><td>تاریخ ثبت</td><td><?= jdate_human($view['created_at']) ?></td></tr>
            </table>
        </div>
        <div>
            <h4>تغییر وضعیت</h4>
            <form method="post" class="p-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="order_id" value="<?= (int)$view['id'] ?>">
                <label>وضعیت جدید
                    <select name="status">
                        <?php foreach (order_statuses() as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $view['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>یادداشت (به مشتری پیام می‌شود)
                    <input name="note" placeholder="مثلاً: کد رهگیری پست: ۱۲۳…">
                </label>
                <button class="btn btn-primary" type="submit" onclick="return confirm('وضعیت سفارش تغییر کند؟')">💾 ثبت تغییر وضعیت</button>
            </form>
            <?php if ($view['status'] === 'cancelled'): ?><div class="p-note">این سفارش لغو شده و اقلامش به انبار برگشت خورده است.</div><?php endif; ?>
        </div>
    </div>

    <h4>اقلام</h4>
    <table class="p-table">
        <thead><tr><th>کالا</th><th>متراژ</th><th>قیمت واحد</th><th>جمع</th></tr></thead>
        <tbody>
        <?php foreach ($viewItems as $it): ?>
            <tr><td><?= e($it['product_name']) ?></td><td><?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></td>
                <td><?= fa_num(number_format((float)$it['unit_price'])) ?></td><td><?= price($it['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="sumrow"><span>جمع کالاها:</span><b><?= price($view['subtotal']) ?></b></div>
    <?php if ((float)$view['discount'] > 0): ?><div class="sumrow ok"><span>تخفیف<?= $view['coupon_code'] ? ' (' . e($view['coupon_code']) . ')' : '' ?>:</span><b>−<?= price($view['discount']) ?></b></div><?php endif; ?>
    <div class="sumrow"><span>ارسال:</span><b><?= (float)$view['shipping_cost'] ? price($view['shipping_cost']) : 'رایگان' ?></b></div>
    <div class="sumrow total"><span>مبلغ کل:</span><b><?= price($view['total']) ?></b></div>

    <?php if ($viewInvs): ?>
    <h4>فاکتورها</h4>
    <table class="p-table">
        <thead><tr><th>کد</th><th>مبلغ</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($viewInvs as $inv): ?>
            <tr>
                <td dir="ltr"><?= e($inv['code']) ?></td>
                <td><?= price($inv['total']) ?></td>
                <td><span class="tag tag-<?= invoice_status_class($inv['status']) ?>"><?= invoice_status_label($inv['status']) ?></span></td>
                <td><a class="btn btn-sm btn-ghost" href="../invoice.php?code=<?= e($inv['code']) ?>" target="_blank">چاپ</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h4>تاریخچه</h4>
    <ul class="history">
        <?php foreach ($viewHist as $h): ?>
            <li><span class="tag tag-<?= order_status_class($h['status']) ?>"><?= order_status_label($h['status']) ?></span> <?= e($h['note']) ?> <small class="muted">— <?= e($h['uname'] ?: 'سیستم') ?>، <?= jdate_human($h['created_at']) ?></small></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php else: ?>
<div class="p-box">
    <form class="p-filter" method="get">
        <input name="q" placeholder="کد سفارش، نام یا موبایل…" value="<?= e($fq) ?>">
        <select name="status">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach (order_statuses() as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">فیلتر</button>
    </form>
    <table class="p-table">
        <thead><tr><th>کد</th><th>مشتری</th><th>موبایل</th><th>اقلام</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $o): ?>
            <tr>
                <td dir="ltr"><b><?= e($o['code']) ?></b></td>
                <td><?= e($o['customer_name']) ?></td>
                <td dir="ltr"><?= e($o['phone']) ?></td>
                <td><?= fa_num($o['items_count']) ?></td>
                <td><?= price($o['total']) ?></td>
                <td><span class="tag tag-<?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
                <td><small><?= jdate_human($o['created_at']) ?></small></td>
                <td><a class="btn btn-sm btn-primary" href="orders.php?view=<?= (int)$o['id'] ?>">مدیریت</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <nav class="pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'on' : '' ?>" href="orders.php?q=<?= urlencode($fq) ?>&status=<?= e($fStatus) ?>&p=<?= $i ?>"><?= fa_num($i) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
