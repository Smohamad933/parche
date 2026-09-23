<?php
require_once __DIR__ . '/app/init.php';

$code = trim($_GET['code'] ?? '');
$inv = $code ? q1("SELECT * FROM invoices WHERE code = ?", [$code]) : null;
if (!$inv) { http_response_code(404); flash_set('e', 'فاکتور یافت نشد.'); redirect('index.php'); }
$order = $inv['order_id'] ? q1("SELECT * FROM orders WHERE id = ?", [$inv['order_id']]) : null;
$seller = $inv['seller_id'] ? q1("SELECT * FROM users WHERE id = ?", [$inv['seller_id']]) : null;

/* اقلام فاکتور از سفارش مرتبط (به تفکیک فروشنده) */
$items = [];
if ($order) {
    $all = q("SELECT oi.*, p.id AS pid FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?", [$order['id']])->fetchAll();
    foreach ($all as $it) {
        if ((int)($it['seller_id'] ?: 0) === (int)($inv['seller_id'] ?: 0)) $items[] = $it;
    }
}
$pageTitle = 'فاکتور ' . $inv['code'];
$print = !empty($_GET['print']);
include __DIR__ . '/inc/header.php';
?>
<div class="invoice <?= $print ? 'printing' : '' ?>">
    <div class="inv-actions no-print">
        <button class="btn btn-primary" onclick="window.print()"><?= icon('printer') ?> چاپ فاکتور</button>
        <?php if ($order): ?><a class="btn btn-ghost" href="track.php?code=<?= e($order['code']) ?>">پیگیری سفارش <?= icon('arrow-left') ?></a><?php endif; ?>
    </div>

    <div class="inv-sheet" id="invSheet">
        <div class="inv-head">
            <div>
                <h2><?= icon('scissors') ?> <?= e(setting('site_name', 'پارچه‌سرا')) ?></h2>
                <p class="muted"><?= e(setting('address')) ?></p>
                <p class="muted"><?= icon('phone') ?> <?= e(setting('phone')) ?></p>
            </div>
            <div class="inv-meta">
                <h3>فاکتور فروش</h3>
                <p>شماره: <b dir="ltr"><?= e($inv['code']) ?></b></p>
                <p>تاریخ: <?= jdate('Y/m/d', strtotime($inv['issued_at'])) ?></p>
                <p>وضعیت: <span class="tag tag-<?= invoice_status_class($inv['status']) ?>"><?= invoice_status_label($inv['status']) ?></span></p>
                <?php if ($inv['paid_at']): ?><p>تاریخ پرداخت: <?= jdate('Y/m/d', strtotime($inv['paid_at'])) ?></p><?php endif; ?>
            </div>
        </div>

        <table class="inv-parties">
            <tr>
                <td><b>فروشنده:</b> <?= e($seller['shop_name'] ?: setting('site_name', 'پارچه‌سرا')) ?><?php if ($seller && $seller['phone']): ?> — <?= icon('phone') ?> <?= e($seller['phone']) ?><?php endif; ?></td>
                <td><b>خریدار:</b> <?= e($inv['customer_name']) ?> — <?= icon('phone') ?> <?= e($inv['phone']) ?></td>
            </tr>
            <?php if ($order): ?>
            <tr>
                <td colspan="2"><b>سفارش:</b> <span dir="ltr"><?= e($order['code']) ?></span> — <b>نشانی:</b> <?= e(($order['city'] ? $order['city'] . '، ' : '') . $order['address']) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <table class="inv-items">
            <thead>
                <tr><th>#</th><th>شرح کالا</th><th>مقدار (متر)</th><th>قیمت واحد (تومان)</th><th>مبلغ کل (تومان)</th></tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $it): ?>
                <tr>
                    <td><?= fa_num($i++) ?></td>
                    <td><?= e($it['product_name']) ?></td>
                    <td><?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></td>
                    <td><?= fa_num(number_format((float)$it['unit_price'])) ?></td>
                    <td><?= fa_num(number_format((float)$it['total'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4">جمع کل</td><td><?= fa_num(number_format((float)$inv['subtotal'])) ?></td></tr>
                <?php if ((float)$inv['discount'] > 0): ?>
                <tr><td colspan="4">تخفیف</td><td><?= fa_num(number_format((float)$inv['discount'])) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$inv['tax'] > 0): ?>
                <tr><td colspan="4">مالیات</td><td><?= fa_num(number_format((float)$inv['tax'])) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td colspan="4">مبلغ قابل پرداخت</td><td><?= fa_num(number_format((float)$inv['total'])) ?> تومان</td></tr>
            </tfoot>
        </table>

        <div class="inv-foot">
            <p class="muted">این فاکتور به‌صورت الکترونیکی صادر شده است. مهلت مرجوعی کالا ۷ روز از تاریخ تحویل است.</p>
            <div class="inv-sign">
                <span>امضای فروشنده</span>
                <span>امضای خریدار</span>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
