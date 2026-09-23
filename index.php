<?php
require_once __DIR__ . '/app/init.php';
$pageTitle = 'فروشگاه اینترنتی پارچه';

$featured = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
               FROM products p WHERE p.status = 'active' AND p.is_featured = 1 ORDER BY p.id DESC LIMIT 4")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<section class="hero">
    <div class="wrap hero-in">
        <div class="hero-text">
            <span class="hero-kicker"><?= icon('scissors') ?> انتخاب پارچه برای دوخت و دکور</span>
            <h1><?= e(setting('hero_title', 'پارچه را ساده انتخاب کنید')) ?></h1>
            <p class="hero-sub"><?= e(setting('hero_subtitle', 'پارچه‌های کاربردی و مجلسی را با قیمت هر متر ببینید، مقایسه کنید و سفارش بدهید.')) ?></p>
            <div class="hero-actions">
                <a class="btn btn-gold btn-lg" href="category.php"><?= icon('shopping-bag') ?> دیدن پارچه‌ها</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-img-wrap">
                <img src="uploads/hero.jpg" alt="فروشگاه پارچه <?= e(setting('site_name')) ?>">
            </div>
        </div>
    </div>
</section>


<section class="sec wrap">
    <div class="sec-head">
        <div class="sec-title-group">
            <h2 class="sec-title">دسته‌بندی پارچه‌ها</h2>
        </div>
    </div>
    <div class="cat-cards">
        <?php foreach (category_tree() as $top): ?>
        <a class="cat-card" href="category.php?id=<?= (int)$top['id'] ?>">
            <span class="cat-ico"><?= icon(['پارچه‌های طبیعی'=>'leaf','پارچه‌های شیمیایی و براق'=>'gem','پارچه‌های مزون و مجلسی'=>'crown','پارچه‌های کاربردی'=>'shirt'][$top['name']] ?? 'scissors') ?></span>
            <div class="cat-txt"><b><?= e($top['name']) ?></b></div>
            <span class="cat-arrow"><?= icon('arrow-left') ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="sec sec-tint">
    <div class="wrap">
        <div class="sec-head">
            <div class="sec-title-group">
                <h2 class="sec-title">پارچه‌های منتخب</h2>
            </div>
            <a class="more" href="category.php?sort=popular">مشاهده همه <?= icon('arrow-left') ?></a>
        </div>
        <div class="grid">
            <?php foreach ($featured as $p) include __DIR__ . '/inc/product-card.php'; ?>
        </div>
    </div>
</section>


<?php include __DIR__ . '/inc/footer.php'; ?>
