<?php
require_once __DIR__ . '/app/init.php';
$pageTitle = 'فروشگاه جامع پارچه';

$featured = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
               FROM products p WHERE p.status = 'active' AND p.is_featured = 1 ORDER BY p.id DESC LIMIT 8");
$newest = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
             FROM products p WHERE p.status = 'active' ORDER BY p.id DESC LIMIT 8");
$stats = [
    'products' => (int)qv("SELECT COUNT(*) FROM products WHERE status='active'"),
    'cats' => (int)qv("SELECT COUNT(*) FROM categories WHERE is_active=1"),
    'customers' => (int)qv("SELECT COUNT(*) FROM users WHERE role='customer'"),
    'orders' => (int)qv("SELECT COUNT(*) FROM orders"),
];
include __DIR__ . '/inc/header.php';
?>
<section class="hero">
    <div class="hero-text">
        <h1><?= e(setting('hero_title', 'فروشگاه پارچه')) ?></h1>
        <p><?= e(setting('hero_subtitle')) ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="category.php">مشاهده همه پارچه‌ها</a>
            <a class="btn btn-ghost" href="track.php">پیگیری سفارش <?= icon('arrow-left') ?></a>
        </div>
        <div class="coupon-chip"><?= icon('gift') ?> کد تخفیف اولین خرید: <b dir="ltr">WELCOME10</b> (۱۰٪)</div>
    </div>
    <div class="hero-img" style="background-image:url('uploads/hero.jpg')"></div>
</section>

<section class="stats-strip">
    <div><b><?= fa_num($stats['products']) ?>+</b><span>پارچه موجود</span></div>
    <div><b><?= fa_num($stats['cats']) ?></b><span>دسته‌بندی</span></div>
    <div><b><?= fa_num($stats['customers']) ?>+</b><span>مشتری</span></div>
    <div><b><?= fa_num($stats['orders']) ?>+</b><span>سفارش ثبت‌شده</span></div>
</section>

<section class="sec">
    <h2 class="sec-title">دسته‌بندی پارچه‌ها</h2>
    <div class="cat-cards">
        <?php foreach (category_tree() as $top): ?>
        <a class="cat-card" href="category.php?id=<?= (int)$top['id'] ?>">
            <span class="cat-ico"><?= icon(['پارچه‌های طبیعی'=>'leaf','پارچه‌های شیمیایی و براق'=>'gem','پارچه‌های مزون و مجلسی'=>'crown','پارچه‌های کاربردی'=>'shirt'][$top['name']] ?? 'scissors') ?></span>
            <b><?= e($top['name']) ?></b>
            <span class="cat-children">
                <?php foreach (array_slice($top['children'], 0, 4) as $ch): ?>
                    <i><?= e($ch['name']) ?></i>
                <?php endforeach; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="sec">
    <div class="sec-head">
        <h2 class="sec-title">پارچه‌های پیشنهادی</h2>
        <a class="more" href="category.php?sort=popular">مشاهده همه <?= icon('arrow-left') ?></a>
    </div>
    <div class="grid">
        <?php foreach ($featured as $p) include __DIR__ . '/inc/product-card.php'; ?>
    </div>
</section>

<section class="sec">
    <div class="sec-head">
        <h2 class="sec-title">جدیدترین پارچه‌ها</h2>
        <a class="more" href="category.php">مشاهده همه <?= icon('arrow-left') ?></a>
    </div>
    <div class="grid">
        <?php foreach ($newest as $p) include __DIR__ . '/inc/product-card.php'; ?>
    </div>
</section>

<section class="cta-strip">
    <div>
        <h3>فروشنده هستید؟</h3>
        <p>با ثبت‌نام فروشندگان، محصولات خود را در پارچینو بفروشید؛ فاکتور و مدیریت انبار اختصاصی بگیرید. با پشتیبانی تماس بگیرید.</p>
    </div>
    <a class="btn btn-gold" href="about.php">اطلاعات بیشتر</a>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
