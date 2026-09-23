<?php
require_once __DIR__ . '/app/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $r = cart_add((int)($_POST['product_id'] ?? 0), (float)en_num($_POST['qty'] ?? 0));
        flash_set($r['ok'] ? 's' : 'e', $r['msg']);
        redirect('cart.php');
    }
    if ($action === 'update') {
        if (!empty($_POST['remove_id'])) {
            cart_remove((int)$_POST['remove_id']);
            flash_set('s', 'کالا از سبد حذف شد.');
            redirect('cart.php');
        }
        foreach ((array)($_POST['qty'] ?? []) as $pid => $qty) cart_update((int)$pid, (float)en_num($qty));
        flash_set('s', 'سبد خرید به‌روزرسانی شد.');
        redirect('cart.php');
    }
    if ($action === 'remove') { cart_remove((int)($_POST['product_id'] ?? 0)); redirect('cart.php'); }
    if ($action === 'clear') { cart_clear(); redirect('cart.php'); }
}

$cart = cart_items();
$pageTitle = 'سبد خرید';
include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title">🛒 سبد خرید</h1>

<?php if (!$cart['items']): ?>
    <div class="empty">سبد خرید شما خالی است. <a href="category.php">مشاهده پارچه‌ها ←</a></div>
<?php else: ?>
<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update">
    <table class="cart-table">
        <thead><tr><th>پارچه</th><th>قیمت هر متر</th><th>متراژ</th><th>جمع</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cart['items'] as $it): $p = $it['product']; ?>
            <tr>
                <td class="cart-p">
                    <img src="<?= product_image($p['img'] ?? null) ?>" alt="">
                    <a href="product.php?id=<?= (int)$p['id'] ?>"><?= e($p['name']) ?></a>
                </td>
                <td><?= fa_num(number_format((float)$p['price'])) ?></td>
                <td>
                    <div class="qty qty-sm">
                        <input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= e(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?>" min="0.5" max="<?= e((string)$p['stock']) ?>" step="0.5" dir="ltr">
                    </div>
                </td>
                <td><b><?= price($it['line_total']) ?></b></td>
                <td>
                    <button class="icon-btn" type="submit" name="remove_id" value="<?= (int)$p['id'] ?>" title="حذف">🗑</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="cart-actions">
        <button class="btn btn-ghost" type="submit">به‌روزرسانی متراژ</button>
        <button class="btn btn-ghost" type="submit" name="action" value="clear">خالی کردن سبد</button>
        <span class="spacer"></span>
        <a class="btn btn-ghost" href="category.php">ادامه خرید</a>
    </div>
</form>

<div class="cart-summary">
    <div class="sumrow"><span>جمع کل:</span><b><?= price($cart['subtotal']) ?></b></div>
    <div class="sumrow muted"><small>هزینه ارسال در مرحله بعد محاسبه می‌شود (ارسال رایگان بالای <?= price(setting('free_shipping_min', 3000000)) ?>)</small></div>
    <a class="btn btn-primary btn-lg btn-block" href="checkout.php">ادامه و ثبت سفارش ←</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>
