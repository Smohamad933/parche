<?php if (!defined('PARCHE')) exit; ?>
</main>
<footer class="site-footer">
    <div class="feat-strip">
        <div class="wrap feat-in">
            <div class="feat"><?= icon('truck') ?><div><b>ارسال سریع</b><span>به سراسر کشور</span></div></div>
            <div class="feat"><?= icon('badge-check') ?><div><b>ضمانت کیفیت</b><span>کنترل قبل از ارسال</span></div></div>
            <div class="feat"><?= icon('rotate-ccw') ?><div><b>مرجوعی ۷ روزه</b><span>بدون قید و شرط</span></div></div>
            <div class="feat"><?= icon('shield-check') ?><div><b>خرید مطمئن</b><span>پشتیبانی واقعی</span></div></div>
        </div>
    </div>
    <div class="wrap cols">
        <div class="f-brand">
            <h4><span class="f-logo"><?= icon('scissors') ?></span> <?= e(setting('site_name', 'پارچینو')) ?></h4>
            <p class="f-desc"><?= nl2br(e(mb_substr(setting('about_text'), 0, 170))) ?>…</p>
            <div class="f-social">
                <?php if (setting('whatsapp')): ?><a class="soc" href="https://wa.me/<?= e(preg_replace('/^0/', '98', en_num(setting('whatsapp')))) ?>" target="_blank" rel="noopener" title="واتساپ"><?= icon('message-square') ?></a><?php endif; ?>
                <?php if (setting('instagram')): ?><a class="soc" href="https://instagram.com/<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" title="اینستاگرام"><?= icon('eye') ?></a><?php endif; ?>
                <?php if (setting('telegram')): ?><a class="soc" href="https://t.me/<?= e(setting('telegram')) ?>" target="_blank" rel="noopener" title="تلگرام"><?= icon('send') ?></a><?php endif; ?>
            </div>
        </div>
        <div class="f-col">
            <h5>دسترسی سریع</h5>
            <a href="index.php">صفحه اصلی</a>
            <a href="category.php">همه دسته‌بندی‌ها</a>
            <a href="track.php">پیگیری سفارش</a>
            <a href="account.php">حساب کاربری</a>
            <a href="about.php">درباره ما</a>
        </div>
        <div class="f-col">
            <h5>دسته‌بندی پارچه‌ها</h5>
            <?php foreach (array_slice(category_tree(), 0, 5) as $top): ?>
                <a href="category.php?id=<?= (int)$top['id'] ?>"><?= e($top['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="f-col">
            <h5>تماس با ما</h5>
            <?php if (setting('address')): ?><p><?= icon('map-pin') ?> <?= e(setting('address')) ?></p><?php endif; ?>
            <?php if (setting('mobile')): ?><p><?= icon('smartphone') ?> <?= fa_num(en_num(setting('mobile'))) ?></p><?php endif; ?>
            <?php if (setting('phone')): ?><p><?= icon('phone') ?> <?= e(setting('phone')) ?></p><?php endif; ?>
            <?php if (setting('email')): ?><p><?= icon('mail') ?> <?= e(setting('email')) ?></p><?php endif; ?>
        </div>
    </div>
    <div class="wrap copy">
        <span>© <?= jdate('Y') ?> <?= e(setting('site_name', 'پارچینو')) ?> — <?= e(setting('footer_note')) ?></span>
    </div>
</footer>
<script src="assets/js/app.js"></script>
</body>
</html>
