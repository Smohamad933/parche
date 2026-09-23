<?php
require_once __DIR__ . '/app/init.php';

$id = (int)($_GET['id'] ?? 0);
$p = q1("SELECT p.*, u.shop_name FROM products p LEFT JOIN users u ON u.id = p.seller_id WHERE p.id = ? AND p.status = 'active'", [$id]);
if (!$p) { http_response_code(404); flash_set('e', 'محصول یافت نشد.'); redirect('category.php'); }
q("UPDATE products SET views = views + 1 WHERE id = ?", [$id]);

$imgs = q("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id", [$id])->fetchAll();
$catPath = category_path((int)$p['category_id']);
$cat = end($catPath) ?: null;
$related = $cat ? q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
                     FROM products p WHERE p.category_id = ? AND p.id != ? AND p.status='active' ORDER BY p.views DESC, p.id DESC LIMIT 4", [$cat['id'], $id])->fetchAll() : [];
$reviews = q("SELECT r.*, u.name AS uname FROM reviews r LEFT JOIN users u ON u.id = r.user_id
              WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.id DESC LIMIT 10", [$id])->fetchAll();
$ratingAvg = qv("SELECT AVG(rating) FROM reviews WHERE product_id = ? AND status='approved'", [$id]);
$canReview = (bool)current_user();

/* ثبت نظر */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review') {
    csrf_verify();
    require_login();
    $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment = trim($_POST['comment'] ?? '');
    if (mb_strlen($comment) < 5) {
        flash_set('e', 'متن نظر خیلی کوتاه است.');
    } else {
        q("INSERT INTO reviews (product_id, user_id, name, rating, comment, status, created_at) VALUES (?,?,?,?,?,'pending',?)",
            [$id, current_user()['id'], current_user()['name'], $rating, $comment, now()]);
        flash_set('s', 'نظر شما ثبت شد و پس از تایید نمایش داده می‌شود.');
    }
    redirect('product.php?id=' . $id);
}

$pageTitle = $p['name'];
include __DIR__ . '/inc/header.php';
?>
<div class="breadcrumb"><a href="index.php">خانه</a> <?php foreach ($catPath as $cp): ?>‹ <a href="category.php?id=<?= (int)$cp['id'] ?>"><?= e($cp['name']) ?></a> <?php endforeach; ?></div>

<div class="product-page">
    <div class="gallery">
        <div class="main-img"><img id="mainImg" src="<?= product_image($imgs[0]['path'] ?? null) ?>" alt="<?= e($p['name']) ?>"></div>
        <?php if (count($imgs) > 1): ?>
        <div class="thumbs">
            <?php foreach ($imgs as $im): ?>
                <img src="<?= e($im['path']) ?>" onclick="document.getElementById('mainImg').src=this.src">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="pinfo">
        <h1><?= e($p['name']) ?></h1>
        <div class="pinfo-sub">
            <span class="muted">کد: <b dir="ltr"><?= e($p['code']) ?></b></span>
            <?php if ($p['shop_name']): ?><span class="muted">فروشنده: <b><?= e($p['shop_name']) ?></b></span><?php endif; ?>
            <?php if ($ratingAvg): ?><span class="rate"><?= stars($ratingAvg) ?> <small><?= fa_num(number_format($ratingAvg, 1)) ?></small></span><?php endif; ?>
            <span class="muted"><?= icon('eye') ?> <?= fa_num((int)$p['views']) ?> بازدید</span>
        </div>

        <div class="price-box">
            <?php if (!empty($p['old_price']) && (float)$p['old_price'] > (float)$p['price']): ?>
                <del><?= price($p['old_price']) ?></del>
            <?php endif; ?>
            <div class="price-now"><b><?= fa_num(number_format((float)$p['price'])) ?></b> تومان <span class="unit">/ هر <?= e($p['unit']) ?></span></div>
        </div>

        <table class="specs">
            <tr><td>جنس</td><td><?= e($p['material'] ?: '—') ?></td></tr>
            <tr><td>رنگ</td><td><?= e($p['color'] ?: '—') ?></td></tr>
            <tr><td>طرح</td><td><?= e($p['pattern'] ?: '—') ?></td></tr>
            <tr><td>عرض</td><td><?= $p['width_cm'] ? fa_num($p['width_cm']) . ' سانتی‌متر' : '—' ?></td></tr>
            <tr><td>مبدا</td><td><?= e($p['origin'] ?: '—') ?></td></tr>
            <tr><td>حداقل سفارش</td><td><?= fa_num(rtrim(rtrim((string)$p['min_order'], '0'), '.')) ?> <?= e($p['unit']) ?></td></tr>
        </table>

        <div class="stock-line">
            <?php if ((float)$p['stock'] > 0): ?>
                <span class="dot g"></span> موجود در انبار: <b><?= fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) ?></b> <?= e($p['unit']) ?>
            <?php else: ?>
                <span class="dot r"></span> این پارچه فعلاً ناموجود است
            <?php endif; ?>
        </div>

        <?php if ((float)$p['stock'] > 0): ?>
        <form class="buy-box" method="post" action="cart.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
            <label>متراژ (<?= e($p['unit']) ?>):</label>
            <div class="qty">
                <button type="button" onclick="qtyStep(this,-0.5)">−</button>
                <input type="number" name="qty" value="<?= e(rtrim(rtrim((string)$p['min_order'], '0'), '.')) ?>" min="<?= e(rtrim(rtrim((string)$p['min_order'], '0'), '.')) ?>" max="<?= e((string)$p['stock']) ?>" step="0.5" dir="ltr">
                <button type="button" onclick="qtyStep(this,+0.5)">+</button>
            </div>
            <button class="btn btn-primary btn-lg" type="submit"><?= icon('shopping-cart') ?> افزودن به سبد خرید</button>
        </form>
        <?php endif; ?>

        <div class="trust">
            <span><?= icon('badge-check') ?> کنترل کیفیت قبل از ارسال</span>
            <span><?= icon('rotate-ccw') ?> ۷ روز مهلت مرجوعی</span>
            <span><?= icon('truck') ?> ارسال سریع از بازار تهران</span>
        </div>
    </div>
</div>

<section class="sec">
    <h2 class="sec-title">توضیحات</h2>
    <div class="desc"><?= nl2br(e($p['description'])) ?></div>
</section>

<section class="sec">
    <h2 class="sec-title">نظرات مشتریان <?= $ratingAvg ? '(' . fa_num(count($reviews)) . ' نظر)' : '' ?></h2>
    <div class="reviews">
        <?php foreach ($reviews as $r): ?>
        <div class="review">
            <div class="review-head"><b><?= e($r['name']) ?></b> <?= stars($r['rating']) ?> <small class="muted"><?= jdate_human($r['created_at']) ?></small></div>
            <p><?= nl2br(e($r['comment'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><p class="muted">هنوز نظری ثبت نشده؛ اولین نفر باشید!</p><?php endif; ?>

        <?php if ($canReview): ?>
        <form class="review-form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="review">
            <h4>ثبت نظر شما</h4>
            <label>امتیاز:
                <select name="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= fa_num($i) ?> ستاره</option><?php endfor; ?>
                </select>
            </label>
            <textarea name="comment" rows="3" placeholder="تجربه خود از کیفیت این پارچه را بنویسید…"></textarea>
            <button class="btn btn-primary" type="submit">ارسال نظر</button>
        </form>
        <?php else: ?>
        <p class="muted">برای ثبت نظر <a href="login.php?next=<?= urlencode('product.php?id=' . $id) ?>">وارد حساب خود</a> شوید.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
<section class="sec">
    <h2 class="sec-title">پارچه‌های مشابه</h2>
    <div class="grid">
        <?php foreach ($related as $rp) { $p2 = $p; $p = $rp; include __DIR__ . '/inc/product-card.php'; $p = $p2; } ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>
