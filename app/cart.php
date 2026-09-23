<?php
/**
 * سبد خرید (بر پایه سشن) + کد تخفیف
 * ساختار: $_SESSION['cart'] = [ product_id => qty(متر) ]
 */
if (!defined('PARCHE')) exit;

function cart_items() {
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) return ['items' => [], 'subtotal' => 0, 'count' => 0];
    $ids = array_map('intval', array_keys($cart));
    $rows = q("SELECT * FROM products WHERE id IN (" . in_ph($ids) . ") AND status = 'active'", $ids);
    $items = [];
    $subtotal = 0;
    foreach ($rows as $p) {
        $qty = (float)($cart[$p['id']] ?? 0);
        if ($qty <= 0) continue;
        $qty = min($qty, (float)$p['stock']); // موجودی لحظه‌ای
        $line = $qty * (float)$p['price'];
        $subtotal += $line;
        $items[] = ['product' => $p, 'qty' => $qty, 'line_total' => $line];
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'count' => count($items)];
}

function cart_count() {
    return count($_SESSION['cart'] ?? []);
}

function cart_add($productId, $qty) {
    $p = q1("SELECT * FROM products WHERE id = ? AND status = 'active'", [(int)$productId]);
    if (!$p) return ['ok' => false, 'msg' => 'محصول یافت نشد.'];
    $qty = max((float)$p['min_order'], (float)$qty);
    if ($qty > (float)$p['stock']) return ['ok' => false, 'msg' => 'موجودی کافی نیست؛ حداکثر ' . fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) . ' متر موجود است.'];
    $cur = (float)($_SESSION['cart'][$p['id']] ?? 0) + $qty;
    $_SESSION['cart'][$p['id']] = min($cur, (float)$p['stock']);
    return ['ok' => true, 'msg' => '«' . $p['name'] . '» به سبد خرید اضافه شد.'];
}

function cart_update($productId, $qty) {
    $pid = (int)$productId;
    if ($qty <= 0) { unset($_SESSION['cart'][$pid]); return; }
    $_SESSION['cart'][$pid] = $qty;
}

function cart_remove($productId) { unset($_SESSION['cart'][(int)$productId]); }
function cart_clear() { unset($_SESSION['cart']); }

/* اعتبارسنجی کد تخفیف؛ خروجی: [code, amount] یا خطا */
function coupon_apply($code, $subtotal) {
    $code = strtoupper(trim(en_num($code)));
    if (!$code) return [false, 'کد تخفیف را وارد کنید.'];
    $c = q1("SELECT * FROM coupons WHERE UPPER(code) = ? AND is_active = 1", [$code]);
    if (!$c) return [false, 'کد تخفیف معتبر نیست.'];
    if ($c['expires_at'] && $c['expires_at'] < now()) return [false, 'این کد تخفیف منقضی شده است.'];
    if ($c['max_uses'] && (int)$c['used_count'] >= (int)$c['max_uses']) return [false, 'ظرفیت استفاده از این کد تکمیل شده است.'];
    if ($subtotal < (float)$c['min_amount']) return [false, 'حداقل مبلغ سفارش برای این کد ' . price($c['min_amount']) . ' است.'];
    $amount = $c['type'] === 'percent' ? (int)round($subtotal * (float)$c['value'] / 100) : (int)$c['value'];
    $amount = min($amount, (int)$subtotal);
    return [true, ['code' => $c['code'], 'amount' => $amount, 'label' => ($c['type'] === 'percent' ? fa_num($c['value']) . '٪ تخفیف' : price($c['value']) . ' تخفیف')]];
}
