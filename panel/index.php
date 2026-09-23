<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'داشبورد';

$isAdmin = is_admin();
$uid = current_user()['id'];

/* آمار فروش (محاسبه در PHP برای سازگاری کامل MySQL و SQLite) */
$today = 0;
if ($isAdmin) {
    foreach (q("SELECT total, created_at FROM orders WHERE status != 'cancelled'") as $o) {
        if (substr($o['created_at'], 0, 10) === date('Y-m-d')) $today += (float)$o['total'];
    }
} else {
    foreach (q("SELECT oi.total AS t, o.created_at AS ca FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.seller_id = ? AND o.status != 'cancelled'", [$uid]) as $o) {
        if (substr($o['ca'], 0, 10) === date('Y-m-d')) $today += (float)$o['t'];
    }
}
$month = 0;
if ($isAdmin) {
    foreach (q("SELECT total, created_at FROM orders WHERE status != 'cancelled'") as $o) {
        if (substr($o['created_at'], 0, 7) === date('Y-m')) $month += (float)$o['total'];
    }
} else {
    foreach (q("SELECT oi.total AS t, o.created_at AS ca FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.seller_id = ? AND o.status != 'cancelled'", [$uid]) as $o) {
        if (substr($o['ca'], 0, 7) === date('Y-m')) $month += (float)$o['t'];
    }
}

/* فروش ۷ روز اخیر برای نمودار */
$sales7 = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $sales7[$d] = 0;
}
if ($isAdmin) {
    foreach (q("SELECT total, created_at FROM orders WHERE status != 'cancelled' AND created_at >= ?", [date('Y-m-d 00:00:00', strtotime('-6 days'))]) as $o) {
        $d = substr($o['created_at'], 0, 10);
        if (isset($sales7[$d])) $sales7[$d] += (float)$o['total'];
    }
} else {
    foreach (q("SELECT oi.total AS t, o.created_at AS ca FROM order_items oi JOIN orders o ON o.id = oi.order_id
                WHERE oi.seller_id = ? AND o.status != 'cancelled' AND o.created_at >= ?", [$uid, date('Y-m-d 00:00:00', strtotime('-6 days'))]) as $o) {
        $d = substr($o['ca'], 0, 10);
        if (isset($sales7[$d])) $sales7[$d] += (float)$o['t'];
    }
}
$maxSales = max(1, max($sales7 ?: [1]));

/* شمارنده‌ها */
if ($isAdmin) {
    $counts = [
        'orders' => (int)qv("SELECT COUNT(*) FROM orders"),
        'products' => (int)qv("SELECT COUNT(*) FROM products"),
        'customers' => (int)qv("SELECT COUNT(*) FROM users WHERE role='customer'"),
        'sellers' => (int)qv("SELECT COUNT(*) FROM users WHERE role='seller'"),
        'unpaid' => (int)qv("SELECT COUNT(*) FROM invoices WHERE status='unpaid'"),
        'lowstock' => (int)qv("SELECT COUNT(*) FROM products WHERE stock <= low_stock"),
    ];
} else {
    $counts = [
        'orders' => (int)qv("SELECT COUNT(DISTINCT order_id) FROM order_items WHERE seller_id = ?", [$uid]),
        'products' => (int)qv("SELECT COUNT(*) FROM products WHERE seller_id = ?", [$uid]),
        'customers' => (int)qv("SELECT COUNT(DISTINCT o.user_id) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ? AND o.user_id IS NOT NULL", [$uid]),
        'sellers' => 0, 'unpaid' => (int)qv("SELECT COUNT(*) FROM invoices WHERE seller_id = ? AND status='unpaid'", [$uid]),
        'lowstock' => (int)qv("SELECT COUNT(*) FROM products WHERE seller_id = ? AND stock <= low_stock", [$uid]),
    ];
}

/* آخرین سفارش‌ها */
if ($isAdmin) {
    $latestOrders = q("SELECT * FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
} else {
    $latestOrders = q("SELECT DISTINCT o.* FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ? ORDER BY o.id DESC LIMIT 8", [$uid])->fetchAll();
}

/* کمبود موجودی */
$lowItems = $isAdmin
    ? q("SELECT * FROM products WHERE stock <= low_stock AND status='active' ORDER BY stock LIMIT 8")->fetchAll()
    : q("SELECT * FROM products WHERE seller_id = ? AND stock <= low_stock AND status='active' ORDER BY stock LIMIT 8", [$uid])->fetchAll();

/* وضعیت سفارش‌ها */
$statusCounts = [];
foreach (order_statuses() as $k => $lbl) $statusCounts[$k] = 0;
$rows = $isAdmin
    ? q("SELECT status, COUNT(*) AS c FROM orders GROUP BY status")->fetchAll()
    : q("SELECT o.status AS status, COUNT(DISTINCT o.id) AS c FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE oi.seller_id=? GROUP BY o.status", [$uid])->fetchAll();
foreach ($rows as $r) $statusCounts[$r['status']] = (int)$r['c'];

include __DIR__ . '/inc/header.php';
?>
<div class="stat-cards">
    <div class="stat"><span class="ico"><?= icon('wallet') ?></span><div><b><?= price($today) ?></b><small>فروش امروز</small></div></div>
    <div class="stat"><span class="ico"><?= icon('calendar') ?></span><div><b><?= price($month) ?></b><small>فروش این ماه</small></div></div>
    <div class="stat"><span class="ico"><?= icon('clipboard-list') ?></span><div><b><?= fa_num($counts['orders']) ?></b><small>کل سفارش‌ها</small></div></div>
    <div class="stat"><span class="ico"><?= icon('shirt') ?></span><div><b><?= fa_num($counts['products']) ?></b><small>محصولات</small></div></div>
    <div class="stat"><span class="ico"><?= icon('users') ?></span><div><b><?= fa_num($counts['customers']) ?></b><small>مشتریان</small></div></div>
    <?php if ($isAdmin): ?><div class="stat"><span class="ico"><?= icon('store') ?></span><div><b><?= fa_num($counts['sellers']) ?></b><small>فروشندگان</small></div><?php endif; ?>
    <div class="stat <?= $counts['unpaid'] ? 'warn' : '' ?>"><span class="ico"><?= icon('receipt') ?></span><div><b><?= fa_num($counts['unpaid']) ?></b><small>فاکتور پرداخت‌نشده</small></div></div>
    <div class="stat <?= $counts['lowstock'] ? 'danger' : '' ?>"><span class="ico"><?= icon('triangle-alert') ?></span><div><b><?= fa_num($counts['lowstock']) ?></b><small>کمبود موجودی</small></div></div>
</div>

<div class="cols-2">
    <div class="p-box">
        <h3>فروش ۷ روز اخیر</h3>
        <div class="chart">
            <?php foreach ($sales7 as $d => $v): ?>
            <div class="chart-col" title="<?= jdate('Y/m/d', strtotime($d)) ?> — <?= price($v) ?>">
                <div class="chart-bar" style="height:<?= max(4, (int)($v / $maxSales * 120)) ?>px"></div>
                <small><?= fa_num((int)date('d', strtotime($d))) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="p-box">
        <h3>وضعیت سفارش‌ها</h3>
        <?php foreach ($statusCounts as $k => $c): ?>
        <div class="status-row">
            <span class="tag tag-<?= order_status_class($k) ?>"><?= order_status_label($k) ?></span>
            <div class="meter"><span style="width:<?= $counts['orders'] ? (int)($c / max(1, $counts['orders']) * 100) : 0 ?>%"></span></div>
            <b><?= fa_num($c) ?></b>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="cols-2">
    <div class="p-box">
        <h3>آخرین سفارش‌ها</h3>
        <table class="p-table">
            <thead><tr><th>کد</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($latestOrders as $o): ?>
            <tr>
                <td dir="ltr"><?= e($o['code']) ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= price($o['total']) ?></td>
                <td><span class="tag tag-<?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
                <td><small><?= jdate_human($o['created_at']) ?></small></td>
                <td><a class="btn btn-sm btn-ghost" href="orders.php?view=<?= (int)$o['id'] ?>">مشاهده</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="p-box">
        <h3><?= icon('triangle-alert') ?> هشدار کمبود موجودی</h3>
        <?php if (!$lowItems): ?><p class="muted">موجودی همه محصولات مناسب است.</p><?php else: ?>
        <table class="p-table">
            <thead><tr><th>محصول</th><th>موجودی</th><th>حد هشدار</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lowItems as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td class="<?= (float)$p['stock'] <= 0 ? 'txt-danger' : '' ?>"><b><?= fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) ?></b></td>
                <td><?= fa_num($p['low_stock']) ?></td>
                <td><a class="btn btn-sm btn-primary" href="inventory.php?product=<?= (int)$p['id'] ?>">افزایش موجودی</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
