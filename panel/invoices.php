<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'فاکتورها';
$isAdmin = is_admin();
$uid = current_user()['id'];
$scope = $isAdmin ? '' : ' AND seller_id = ' . $uid;

/* تغییر وضعیت پرداخت */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    csrf_verify();
    $iid = (int)($_POST['id'] ?? 0);
    $inv = q1("SELECT * FROM invoices WHERE id = ? AND (1=1 $scope)", [$iid]);
    if ($inv) {
        $newStatus = ($_POST['to'] ?? 'paid') === 'unpaid' ? 'unpaid' : 'paid';
        q("UPDATE invoices SET status = ?, paid_at = ? WHERE id = ?", [$newStatus, $newStatus === 'paid' ? now() : null, $iid]);
        flash_set('s', 'وضعیت فاکتور ' . $inv['code'] . ' به «' . invoice_status_label($newStatus) . '» تغییر کرد.');
    }
    redirect('invoices.php');
}

/* ساخت فاکتور دستی */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    csrf_verify();
    $customer = trim($_POST['customer_name'] ?? '');
    $phone = en_num(trim($_POST['phone'] ?? ''));
    $note = trim($_POST['note'] ?? '');
    $lines = [];
    foreach ((array)($_POST['product'] ?? []) as $i => $pname) {
        $pname = trim($pname);
        $qty = (float)en_num($_POST['qty'][$i] ?? 0);
        $unit = (float)en_num($_POST['unit_price'][$i] ?? 0);
        if ($pname && $qty > 0) $lines[] = [$pname, $qty, $unit, $qty * $unit];
    }
    if (mb_strlen($customer) < 3 || !$lines) {
        flash_set('e', 'نام خریدار و حداقل یک ردیف کالا لازم است.');
    } else {
        $sub = 0; foreach ($lines as $l) $sub += $l[3];
        q("INSERT INTO invoices (code, order_id, seller_id, customer_name, phone, subtotal, discount, tax, total, status, note, issued_at)
          VALUES (NULL, NULL, ?,?,?,?,?,0,?,'unpaid',?,?)",
          [(!$isAdmin || !(int)($_POST['seller_id'] ?? 0)) ? ($isAdmin ? null : $uid) : (int)$_POST['seller_id'], $customer, $phone ?: null, $sub, 0, $sub, $note ?: null, now()]);
        $iid = last_id();
        q("UPDATE invoices SET code = ? WHERE id = ?", [pad_code('INV-', $iid), $iid]);
        flash_set('s', 'فاکتور دستی ساخته شد: ' . pad_code('INV-', $iid));
        redirect('invoices.php?view=' . $iid);
    }
}

/* لیست */
$st = $_GET['status'] ?? '';
$fq = trim($_GET['q'] ?? '');
$where = '1=1' . $scope;
$params = [];
if ($st && array_key_exists($st, invoice_statuses())) { $where .= ' AND status = ?'; $params[] = $st; }
if ($fq !== '') { $where .= ' AND (code LIKE ? OR customer_name LIKE ?)'; $params[] = "%$fq%"; $params[] = "%$fq%"; }
$per = 15;
$page = max(1, (int)($_GET['p'] ?? 1));
$total = (int)qv("SELECT COUNT(*) FROM invoices WHERE $where", $params);
$rows = q("SELECT i.*, u.shop_name FROM invoices i LEFT JOIN users u ON u.id = i.seller_id WHERE $where ORDER BY i.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
$pages = max(1, (int)ceil($total / $per));
$sums = q("SELECT status, COALESCE(SUM(total),0) AS s FROM invoices WHERE 1=1 $scope GROUP BY status")->fetchAll();

$view = !empty($_GET['view']) ? q1("SELECT * FROM invoices WHERE id = ? AND (1=1 $scope)", [(int)$_GET['view']]) : null;

include __DIR__ . '/inc/header.php';
?>
<?php if ($view): ?>
<div class="p-box">
    <div class="box-head">
        <h3>فاکتور <span dir="ltr"><?= e($view['code']) ?></span></h3>
        <div>
            <a class="btn btn-ghost btn-sm" href="invoices.php"><?= icon('arrow-right') ?> بازگشت</a>
            <a class="btn btn-primary btn-sm" href="../invoice.php?code=<?= e($view['code']) ?>" target="_blank"><?= icon('printer') ?> چاپ / مشاهده</a>
        </div>
    </div>
    <table class="p-table kv">
        <tr><td>خریدار</td><td><b><?= e($view['customer_name']) ?></b> (<?= e($view['phone'] ?: '—') ?>)</td></tr>
        <tr><td>سفارش مرتبط</td><td><?= $view['order_id'] ? '<a href="orders.php?view=' . (int)$view['order_id'] . '">مشاهده سفارش</a>' : 'فاکتور دستی' ?></td></tr>
        <tr><td>مبلغ کل</td><td><b><?= price($view['total']) ?></b></td></tr>
        <tr><td>وضعیت</td><td><span class="tag tag-<?= invoice_status_class($view['status']) ?>"><?= invoice_status_label($view['status']) ?></span></td></tr>
        <tr><td>صدور</td><td><?= jdate_human($view['issued_at']) ?></td></tr>
        <tr><td>پرداخت</td><td><?= $view['paid_at'] ? jdate_human($view['paid_at']) : '—' ?></td></tr>
    </table>
    <form method="post" style="margin-top:12px"><?= csrf_field() ?>
        <input type="hidden" name="action" value="pay">
        <input type="hidden" name="id" value="<?= (int)$view['id'] ?>">
        <?php if ($view['status'] !== 'paid'): ?>
            <input type="hidden" name="to" value="paid">
            <button class="btn btn-primary" type="submit"><?= icon('check') ?> ثبت پرداخت</button>
        <?php else: ?>
            <input type="hidden" name="to" value="unpaid">
            <button class="btn btn-ghost" type="submit"><?= icon('rotate-ccw') ?> برگشت به پرداخت‌نشده</button>
        <?php endif; ?>
    </form>
</div>
<?php else: ?>
<div class="cols-2">
    <div class="p-box">
        <h3><?= icon('plus') ?> صدور فاکتور دستی</h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="row2">
                <label>نام خریدار *<input name="customer_name" required></label>
                <label>موبایل<input name="phone" dir="ltr"></label>
            </div>
            <?php if ($isAdmin): ?>
            <label>فروشنده
                <select name="seller_id">
                    <option value="0">— فروشگاه (مدیر) —</option>
                    <?php foreach (q("SELECT id, shop_name, name FROM users WHERE role='seller'")->fetchAll() as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"><?= e($s['shop_name'] ?: $s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
            <div class="inv-lines">
                <div class="row3 head"><span>شرح کالا</span><span>متراژ</span><span>قیمت واحد</span></div>
                <?php for ($i = 0; $i < 3; $i++): ?>
                <div class="row3">
                    <input name="product[]" placeholder="مثلاً ترگال مشکی">
                    <input name="qty[]" dir="ltr" placeholder="مثلاً 3">
                    <input name="unit_price[]" dir="ltr" placeholder="مثلاً 245000">
                </div>
                <?php endfor; ?>
            </div>
            <label>توضیحات<input name="note"></label>
            <button class="btn btn-primary" type="submit"><?= icon('receipt') ?> صدور فاکتور</button>
        </form>
    </div>
    <div class="p-box">
        <h3>خلاصه فاکتورها</h3>
        <?php
        $allSum = 0; $unpaidSum = 0; $paidSum = 0;
        foreach ($sums as $s) {
            if ($s['status'] === 'paid') $paidSum = (float)$s['s'];
            if ($s['status'] === 'unpaid') $unpaidSum = (float)$s['s'];
            if ($s['status'] !== 'cancelled') $allSum += (float)$s['s'];
        }
        ?>
        <div class="stat-cards">
            <div class="stat"><span class="ico"><?= icon('wallet') ?></span><div><b><?= price($paidSum) ?></b><small>وصول‌شده</small></div></div>
            <div class="stat warn"><span class="ico"><?= icon('hourglass') ?></span><div><b><?= price($unpaidSum) ?></b><small>در انتظار پرداخت</small></div></div>
            <div class="stat"><span class="ico"><?= icon('receipt') ?></span><div><b><?= price($allSum) ?></b><small>جمع کل</small></div></div>
        </div>
    </div>
</div>

<div class="p-box">
    <form class="p-filter" method="get">
        <input name="q" placeholder="کد فاکتور یا نام خریدار…" value="<?= e($fq) ?>">
        <select name="status">
            <option value="">همه</option>
            <?php foreach (invoice_statuses() as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">فیلتر</button>
    </form>
    <table class="p-table">
        <thead><tr><th>کد</th><th>خریدار</th><th>فروشنده</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $i): ?>
            <tr>
                <td dir="ltr"><b><?= e($i['code']) ?></b></td>
                <td><?= e($i['customer_name']) ?></td>
                <td><?= e($i['shop_name'] ?: 'فروشگاه') ?></td>
                <td><?= price($i['total']) ?></td>
                <td><span class="tag tag-<?= invoice_status_class($i['status']) ?>"><?= invoice_status_label($i['status']) ?></span></td>
                <td><small><?= jdate_human($i['issued_at']) ?></small></td>
                <td>
                    <a class="btn btn-sm btn-ghost" href="invoices.php?view=<?= (int)$i['id'] ?>">مدیریت</a>
                    <a class="btn btn-sm btn-ghost" href="../invoice.php?code=<?= e($i['code']) ?>" target="_blank">چاپ</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <nav class="pager">
        <?php for ($j = 1; $j <= $pages; $j++): ?>
            <a class="<?= $j === $page ? 'on' : '' ?>" href="invoices.php?q=<?= urlencode($fq) ?>&status=<?= e($st) ?>&p=<?= $j ?>"><?= fa_num($j) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
