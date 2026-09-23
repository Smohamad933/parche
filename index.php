<?php
require_once __DIR__ . '/app/init.php';
$pageTitle = 'فروشگاه اینترنتی پارچه';

$featured = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
               FROM products p WHERE p.status = 'active' AND p.is_featured = 1 ORDER BY p.id DESC LIMIT 8")->fetchAll();
$newest = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
             FROM products p WHERE p.status = 'active' ORDER BY p.id DESC LIMIT 4")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<section class="hero">
    <div class="wrap hero-in">
        <div class="hero-text">
            <span class="hero-kicker"><?= icon('scissors') ?> انتخاب پارچه برای دوخت و دکور</span>
            <h1><?= e(setting('hero_title', 'پارچه را ساده انتخاب کنید')) ?></h1>
            <p class="hero-sub"><?= e(setting('hero_subtitle', 'پارچه‌های کاربردی و مجلسی را با قیمت هر متر ببینید، مقایسه کنید و سفارش بدهید.')) ?></p>
            <div class="hero-actions">
                <a class="btn btn-gold btn-lg" href="category.php"><?= icon('shopping-bag') ?> مشاهده و خرید پارچه</a>
                <a class="btn btn-hero-ghost btn-lg" href="track.php"><?= icon('search') ?> پیگیری سفارش</a>
            </div>
            <div class="hero-mini">
                <span><?= icon('truck') ?> ارسال سریع به سراسر کشور</span>
                <span><?= icon('ruler') ?> فروش به‌صورت متر</span>
                <span><?= icon('rotate-ccw') ?> مرجوعی ۷ روزه</span>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-img-wrap">
                <img src="uploads/hero.jpg" alt="فروشگاه پارچه <?= e(setting('site_name')) ?>">
            </div>
        </div>
    </div>
</section>

<section class="stats-strip wrap">
    <div class="stat-s"><?= icon('truck') ?><div><b>ارسال به سراسر ایران</b><span>ثبت و پیگیری سفارش</span></div></div>
    <div class="stat-s"><?= icon('ruler') ?><div><b>فروش بر اساس متر</b><span>مقدار دقیق برای دوخت</span></div></div>
    <div class="stat-s"><?= icon('badge-check') ?><div><b>توضیحات روشن</b><span>جنس، عرض و رنگ هر کالا</span></div></div>
    <div class="stat-s"><?= icon('message-square') ?><div><b>پشتیبانی مستقیم</b><span><?= fa_num(en_num(setting('mobile', '09125085832'))) ?></span></div></div>
</section>

<section class="sec wrap">
    <div class="sec-head">
        <div class="sec-title-group">
            <h2 class="sec-title">دسته‌بندی پارچه‌ها</h2>
            <p class="sec-sub">از طبیعی تا شیمیایی و مزون — دسته‌بندی‌شده برای انتخاب راحت</p>
        </div>
    </div>
    <div class="cat-cards">
        <?php foreach (category_tree() as $top): ?>
        <a class="cat-card" href="category.php?id=<?= (int)$top['id'] ?>">
            <span class="cat-ico"><?= icon(['پارچه‌های طبیعی'=>'leaf','پارچه‌های شیمیایی و براق'=>'gem','پارچه‌های مزون و مجلسی'=>'crown','پارچه‌های کاربردی'=>'shirt'][$top['name']] ?? 'scissors') ?></span>
            <div class="cat-txt">
                <b><?= e($top['name']) ?></b>
                <span class="cat-children">
                    <?php foreach (array_slice($top['children'], 0, 3) as $ch): ?>
                        <i><?= e($ch['name']) ?></i>
                    <?php endforeach; ?>
                    <?php if (count($top['children']) > 3): ?><i>+<?= fa_num(count($top['children']) - 3) ?></i><?php endif; ?>
                </span>
            </div>
            <span class="cat-arrow"><?= icon('arrow-left') ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="sec sec-tint">
    <div class="wrap">
        <div class="sec-head">
            <div class="sec-title-group">
                <h2 class="sec-title">پارچه‌های پیشنهادی <?= e(setting('site_name')) ?></h2>
                <p class="sec-sub">انتخاب تیم ما؛ پرفروش‌ترین و باکیفیت‌ترین‌ها</p>
            </div>
            <a class="more" href="category.php?sort=popular">مشاهده همه <?= icon('arrow-left') ?></a>
        </div>
        <div class="grid">
            <?php foreach ($featured as $p) include __DIR__ . '/inc/product-card.php'; ?>
        </div>
    </div>
</section>

<section class="sec wrap">
    <div class="sec-head">
        <div class="sec-title-group">
            <h2 class="sec-title">جدیدترین پارچه‌ها</h2>
            <p class="sec-sub">تازه‌ترین افزوده‌ها به انبار <?= e(setting('site_name')) ?></p>
        </div>
        <a class="more" href="category.php">مشاهده همه <?= icon('arrow-left') ?></a>
    </div>
    <div class="grid">
        <?php foreach ($newest as $p) include __DIR__ . '/inc/product-card.php'; ?>
    </div>
</section>

<section class="cta-strip wrap">
    <div class="cta-txt">
        <h3><?= icon('message-square') ?> برای انتخاب پارچه راهنمایی می‌خواهید؟</h3>
        <p>اگر درباره جنس، رنگ یا مقدار سفارش سوالی دارید، قبل از خرید با ما تماس بگیرید. پاسخ‌گویی واقعی از طریق واتساپ انجام می‌شود.</p>
    </div>
    <?php if (setting('mobile')): ?><a class="btn btn-gold" href="https://wa.me/<?= e(preg_replace('/^0/', '98', en_num(setting('mobile')))) ?>" target="_blank" rel="noopener">گفتگو در واتساپ</a><?php endif; ?>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
