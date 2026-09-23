<?php
require_once __DIR__ . '/app/init.php';

$cart = cart_items();
if (!$cart['items']) redirect('cart.php');

$u = current_user();
$coupon = $_SESSION['coupon'] ?? null;

/* ثبت سفارش */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name = trim($_POST['name'] ?? '');
    $phone = en_num(trim($_POST['phone'] ?? ''));
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postal = en_num(trim($_POST['postal_code'] ?? ''));
    $note = trim($_POST['note'] ?? '');
    $couponInput = strtoupper(trim(en_num($_POST['coupon'] ?? '')));

    $errors = [];
    if (mb_strlen($name) < 3) $errors[] = 'نام و نام خانوادگی را کامل وارد کنید.';
    if (!preg_match('/^09\d{9}$/', $phone)) $errors[] = 'شماره موبایل معتبر نیست (مثال: 09121234567).';
    if (mb_strlen($address) < 10) $errors[] = 'آدرس را کامل وارد کنید.';
    if (!$city) $errors[] = 'شهر را وارد کنید.';

    /* محاسبه نهایی با کنترل موجودی */
    $subtotal = 0;
    foreach ($cart['items'] as $it) {
        $fresh = q1("SELECT stock, price FROM products WHERE id = ?", [(int)$it['product']['id']]);
        if (!$fresh || (float)$fresh['stock'] < (float)$it['qty']) {
            $errors[] = 'موجودی «' . $it['product']['name'] . '» تغییر کرده است؛ لطفاً سبد را به‌روزرسانی کنید.';
        }
        $subtotal += (float)$it['qty'] * (float)$it['product']['price'];
    }

    $discount = 0; $couponCode = null;
    if ($couponInput) {
        [$ok, $res] = coupon_apply($couponInput, $subtotal);
        if ($ok) { $discount = (int)$res['amount']; $couponCode = $res['code']; }
        else $errors[] = $res;
    }

    $shipping = ($subtotal - $discount) >= (float)setting('free_shipping_min', 3000000) ? 0 : (float)setting('shipping_flat', 45000);
    $total = $subtotal - $discount + $shipping;

    if (!$errors) {
        tx_start();
        try {
            q("INSERT INTO orders (code, user_id, customer_name, phone, address, city, postal_code, note, subtotal, discount, shipping_cost, total, coupon_code, status, created_at, updated_at)
              VALUES (NULL, ?,?,?,?,?,?,?,?,?,?,?,?,'pending',?,?)",
              [$u['id'] ?? null, $name, $phone, $address, $city, $postal ?: null, $note ?: null,
               $subtotal, $discount, $shipping, $total, $couponCode, now(), now()]);
            $orderId = last_id();
            $code = pad_code('ORD-', $orderId);
            q("UPDATE orders SET code = ? WHERE id = ?", [$code, $orderId]);

            /* آیتم‌ها + کسر موجودی + لاگ انبار */
            foreach ($cart['items'] as $it) {
                $p = $it['product'];
                q("INSERT INTO order_items (order_id, product_id, product_name, seller_id, qty, unit_price, total) VALUES (?,?,?,?,?,?,?)",
                  [$orderId, $p['id'], $p['name'], $p['seller_id'] ?: null, $it['qty'], $p['price'], $it['line_total']]);
                q("UPDATE products SET stock = stock - ? WHERE id = ?", [$it['qty'], $p['id']]);
                $after = (float)$p['stock'] - (float)$it['qty'];
                q("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, order_id, stock_after, created_at) VALUES (?,?,?,'order',?,?,?,?)",
                  [$p['id'], $u['id'] ?? null, -$it['qty'], 'خروج برای سفارش ' . $code, $orderId, $after, now()]);
            }

            /* فاکتور برای هر فروشنده (کالای بدون فروشنده → فاکتور فروشگاه با seller_id=NULL) */
            $bySeller = [];
            foreach ($cart['items'] as $it) {
                $sid = (int)($it['product']['seller_id'] ?: 0);
                $bySeller[$sid][] = $it;
            }
            foreach ($bySeller as $sid => $lines) {
                $sub = 0;
                foreach ($lines as $l) $sub += (float)$l['line_total'];
                $discShare = $subtotal > 0 ? (int)round($discount * $sub / $subtotal) : 0;
                $invTotal = $sub - $discShare;
                q("INSERT INTO invoices (code, order_id, seller_id, customer_name, phone, subtotal, discount, tax, total, status, note, issued_at) VALUES (NULL,?,?,?,?,?,?,0,?,'unpaid',?,?)",
                  [$orderId, $sid ?: null, $name, $phone, $sub, $discShare, $invTotal, 'فاکتور فروش پارچه', now()]);
                $invId = last_id();
                q("UPDATE invoices SET code = ? WHERE id = ?", [pad_code('INV-', $invId), $invId]);
            }

            if ($couponCode) q("UPDATE coupons SET used_count = used_count + 1 WHERE UPPER(code) = ?", [$couponCode]);

            /* تاریخچه سفارش */
            q("INSERT INTO order_history (order_id, status, note, by_user_id, created_at) VALUES (?, 'pending', 'سفارش با موفقیت ثبت شد', ?, ?)",
              [$orderId, $u['id'] ?? null, now()]);

            /* پیام داخلی به مشتری */
            if ($u) {
                q("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (NULL, ?, ?, ?, 0, ?)",
                  [$u['id'], 'ثبت سفارش ' . $code, "سفارش شما با کد $code ثبت شد.\nمبلغ کل: " . price_raw($total) . " تومان\nوضعیت فعلی: در انتظار تایید.\nپس از تایید، همکاران ما با شما تماس می‌گیرند.", now()]);
            }

            tx_commit();
            unset($_SESSION['cart'], $_SESSION['coupon']);
            flash_set('s', "سفارش شما با کد $code ثبت شد.");
            redirect('order-success.php?code=' . urlencode($code));
        } catch (Throwable $ex) {
            tx_rollback();
            $errors[] = 'خطا در ثبت سفارش: ' . $ex->getMessage();
        }
    }
    if (!empty($errors)) foreach ($errors as $er) flash_set('e', $er);
    /* اعمال کد تخفیف معتبر در سشن برای نمایش */
    if ($couponCode) $_SESSION['coupon'] = ['code' => $couponCode, 'amount' => $discount];
}

$pageTitle = 'تسویه حساب';
include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title">ثبت سفارش</h1>

<div class="checkout">
    <form method="post" class="checkout-form">
        <?= csrf_field() ?>
        <h3>اطلاعات گیرنده</h3>
        <div class="row2">
            <label>نام و نام خانوادگی *
                <input name="name" required value="<?= e($u['name'] ?? $_POST['name'] ?? '') ?>">
            </label>
            <label>شماره موبایل *
                <input name="phone" required dir="ltr" placeholder="09121234567" value="<?= e($u['phone'] ?? $_POST['phone'] ?? '') ?>">
            </label>
        </div>
        <div class="row2">
            <label>شهر *
                <input name="city" required value="<?= e($_POST['city'] ?? '') ?>">
            </label>
            <label>کد پستی
                <input name="postal_code" dir="ltr" value="<?= e($_POST['postal_code'] ?? '') ?>">
            </label>
        </div>
        <label>آدرس کامل پستی *
            <textarea name="address" rows="2" required><?= e($_POST['address'] ?? '') ?></textarea>
        </label>
        <label>توضیحات سفارش
            <textarea name="note" rows="2" placeholder="مثلاً: متراژها جدا برش داده شود…"><?= e($_POST['note'] ?? '') ?></textarea>
        </label>

        <h3>کد تخفیف</h3>
        <div class="row2">
            <input name="coupon" placeholder="مثلاً WELCOME10" dir="ltr" value="<?= e($_POST['coupon'] ?? ($coupon['code'] ?? '')) ?>">
        </div>

        <h3>پرداخت</h3>
        <div class="pay-note"><?= icon('credit-card') ?> پرداخت پس از تایید سفارش توسط فروشگاه انجام می‌شود؛ کارشناسان ما برای هماهنگی پرداخت (کارت به کارت / درگاه) با شما تماس می‌گیرند.</div>

        <button class="btn btn-primary btn-lg btn-block" type="submit"><?= icon('check') ?> ثبت نهایی سفارش</button>
    </form>

    <aside class="checkout-sum">
        <h3>خلاصه سفارش</h3>
        <?php foreach ($cart['items'] as $it): ?>
            <div class="sumrow"><span><?= e($it['product']['name']) ?> × <?= fa_num(rtrim(rtrim((string)$it['qty'], '0'), '.')) ?></span><span><?= fa_num(number_format((float)$it['line_total'])) ?></span></div>
        <?php endforeach; ?>
        <hr>
        <div class="sumrow"><span>جمع کالاها:</span><b><?= price($cart['subtotal']) ?></b></div>
        <?php
        $sub = $cart['subtotal'];
        [$okC, $resC] = $couponInput ? coupon_apply($couponInput, $sub) : [false, null];
        $showDisc = $okC ? $resC['amount'] : 0;
        $shipNow = ($sub - $showDisc) >= (float)setting('free_shipping_min', 3000000) ? 0 : (float)setting('shipping_flat', 45000);
        ?>
        <?php if ($showDisc): ?><div class="sumrow ok"><span>تخفیف (<?= e($resC['label']) ?>):</span><b>−<?= price($showDisc) ?></b></div><?php endif; ?>
        <div class="sumrow"><span>هزینه ارسال:</span><b><?= $shipNow ? price($shipNow) : 'رایگان' ?></b></div>
        <div class="sumrow total"><span>مبلغ قابل پرداخت:</span><b><?= price($sub - $showDisc + $shipNow) ?></b></div>
    </aside>
</div>

<?php include __DIR__ . '/inc/footer.php'; ?>
