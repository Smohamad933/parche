<?php
require_once __DIR__ . '/app/init.php';
$code = trim($_GET['code'] ?? '');
$order = $code ? q1("SELECT * FROM orders WHERE code = ?", [$code]) : null;
$items = $order ? q("SELECT * FROM order_items WHERE order_id = ?", [$order['id']])->fetchAll() : [];
$history = $order ? q("SELECT * FROM order_history WHERE order_id = ? ORDER BY id", [$order['id']])->fetchAll() : [];
$pageTitle = 'پیگیری سفارش';
include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title"><?= icon('search') ?> پیگیری سفارش</h1>

<form class="track-form" method="get">
    <input type="text" name="code" placeholder="کد سفارش را وارد کنید (مثلاً ORD-000001)" dir="ltr" value="<?= e($code) ?>" required>
    <button class="btn btn-primary" type="submit">پیگیری</button>
</form>

<?php if ($code && !$order): ?>
    <div class="empty">سفارشی با این کد پیدا نشد.</div>
<?php endif; ?>

<?php if ($order): ?>
<div class="track-result">
    <div class="track-head">
        <div><span class="muted">کد سفارش:</span> <b dir="ltr"><?= e($order['code']) ?></b></div>
        <div><span class="muted">تاریخ ثبت:</span> <?= jdate_human($order['created_at']) ?></div>
        <div><span class="tag tag-<?= order_status_class($order['status']) ?>"><?= order_status_label($order['status']) ?></span></div>
    </div>

    <div class="timeline">
        <?php
        $flow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $curIdx = array_search($order['status'], $flow, true);
        if ($order['status'] === 'cancelled') { $curIdx = -1; }
        foreach ($flow as $i => $st): ?>
            <div class="tl-step <?= $i <= $curIdx ? 'done' : '' ?> <?= $i === $curIdx ? 'now' : '' ?>">
                <span class="tl-dot"></span>
                <span class="tl-label"><?= order_status_label($st) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($order['status'] === 'cancelled'): ?>
        <div class="alert alert-e">این سفارش لغو شده است. در صورت پرداخت، مبلغ حداکثر تا ۷۲ ساعت بازگردانده می‌شود.</div>
    <?php endif; ?>

    <table class="cart-table">
        <thead><tr><th>کالا</th><th>متراژ</th><th>مبلغ</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr><td><?= e($it['product_name']) ?></td><td><?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></td><td><?= price($it['total']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="sumrow total"><span>مبلغ کل:</span><b><?= price($order['total']) ?></b></div>

    <h3>رویدادهای سفارش</h3>
    <ul class="history">
        <?php foreach ($history as $h): ?>
            <li><b><?= order_status_label($h['status']) ?></b> — <?= e($h['note']) ?> <small class="muted">(<?= jdate_human($h['created_at']) ?>)</small></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>
