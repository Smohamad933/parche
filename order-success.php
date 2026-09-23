<?php
require_once __DIR__ . '/app/init.php';
$code = trim($_GET['code'] ?? '');
$order = $code ? q1("SELECT * FROM orders WHERE code = ?", [$code]) : null;
if (!$order) { flash_set('e', 'سفارش یافت نشد.'); redirect('index.php'); }
$items = q("SELECT * FROM order_items WHERE order_id = ?", [$order['id']])->fetchAll();
$invoices = q("SELECT * FROM invoices WHERE order_id = ?", [$order['id']])->fetchAll();
$pageTitle = 'سفارش ثبت شد';
include __DIR__ . '/inc/header.php';
?>
<div class="success-box">
    <div class="success-ico"><?= icon('circle-check') ?></div>
    <h1>سفارش شما با موفقیت ثبت شد!</h1>
    <p>کد سفارش شما: <b class="order-code" dir="ltr"><?= e($order['code']) ?></b></p>
    <p class="muted">این کد را برای پیگیری نگه دارید. به‌زودی برای هماهنگی ارسال و پرداخت با شما تماس می‌گیریم.</p>

    <div class="order-sum-box">
        <h3>اقلام سفارش</h3>
        <table class="cart-table">
            <thead><tr><th>کالا</th><th>متراژ</th><th>مبلغ</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr><td><?= e($it['product_name']) ?></td><td><?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></td><td><?= price($it['total']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="sumrow"><span>جمع:</span><b><?= price($order['subtotal']) ?></b></div>
        <?php if ((float)$order['discount'] > 0): ?><div class="sumrow ok"><span>تخفیف:</span><b>−<?= price($order['discount']) ?></b></div><?php endif; ?>
        <div class="sumrow"><span>ارسال:</span><b><?= (float)$order['shipping_cost'] ? price($order['shipping_cost']) : 'رایگان' ?></b></div>
        <div class="sumrow total"><span>مبلغ کل:</span><b><?= price($order['total']) ?></b></div>
    </div>

    <?php if ($invoices): ?>
    <h3>فاکتور(های) این سفارش</h3>
    <ul class="inv-links">
        <?php foreach ($invoices as $inv): ?>
            <li><a href="invoice.php?code=<?= e($inv['code']) ?>" target="_blank"><?= icon('receipt') ?> فاکتور <?= e($inv['code']) ?> — <?= price($inv['total']) ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="hero-actions" style="margin-top:20px">
        <a class="btn btn-primary" href="track.php?code=<?= e($order['code']) ?>">پیگیری سفارش <?= icon('arrow-left') ?></a>
        <a class="btn btn-ghost" href="category.php">ادامه خرید</a>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
