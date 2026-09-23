<?php
require_once __DIR__ . '/app/init.php';
$pageTitle = 'درباره ما';
include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title">درباره <?= e(setting('site_name', 'پارچینو')) ?></h1>
<div class="about">
    <div class="about-text"><?= nl2br(e(setting('about_text'))) ?></div>
    <div class="about-contact">
        <h3>تماس با ما</h3>
        <?php if (setting('address')): ?><p><?= icon('map-pin') ?> <?= e(setting('address')) ?></p><?php endif; ?>
        <?php if (setting('mobile')): ?><p><?= icon('smartphone') ?> پشتیبانی و سفارش تلفنی: <?= e(setting('mobile')) ?></p><?php endif; ?>
        <?php if (setting('phone')): ?><p><?= icon('phone') ?> <?= e(setting('phone')) ?></p><?php endif; ?>
        <?php if (setting('email')): ?><p><?= icon('mail') ?> <?= e(setting('email')) ?></p><?php endif; ?>
        <p>
            <?php if (setting('instagram')): ?><a class="btn btn-ghost btn-sm" href="https://instagram.com/<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">اینستاگرام</a><?php endif; ?>
            <?php if (setting('telegram')): ?><a class="btn btn-ghost btn-sm" href="https://t.me/<?= e(setting('telegram')) ?>" target="_blank" rel="noopener">تلگرام</a><?php endif; ?>
            <?php if (setting('whatsapp')): ?><a class="btn btn-ghost btn-sm" href="https://wa.me/<?= e(preg_replace('/^0/', '98', en_num(setting('whatsapp')))) ?>" target="_blank" rel="noopener">واتساپ</a><?php endif; ?>
        </p>
        <h3>ساعات کاری</h3>
        <p>شنبه تا پنجشنبه: ۹ تا ۱۹ — جمعه‌ها تعطیل</p>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
