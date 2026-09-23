<?php if (!defined('PARCHE')) exit; ?>
</main>
<footer class="site-footer">
    <div class="wrap cols">
        <div>
            <h4><?= icon('scissors') ?> <?= e(setting('site_name', 'پارچینو')) ?></h4>
            <p><?= nl2br(e(mb_substr(setting('about_text'), 0, 160))) ?>…</p>
        </div>
        <div>
            <h4>دسترسی سریع</h4>
            <a href="index.php">صفحه اصلی</a>
            <a href="category.php">همه دسته‌بندی‌ها</a>
            <a href="track.php">پیگیری سفارش</a>
            <a href="about.php">درباره ما و تماس</a>
        </div>
        <div>
            <h4>تماس با ما</h4>
            <?php if (setting('address')): ?><p><?= icon('map-pin') ?> <?= e(setting('address')) ?></p><?php endif; ?>
            <?php if (setting('mobile')): ?><p><?= icon('smartphone') ?> <?= e(setting('mobile')) ?></p><?php endif; ?>
            <?php if (setting('phone')): ?><p><?= icon('phone') ?> <?= e(setting('phone')) ?></p><?php endif; ?>
            <?php if (setting('email')): ?><p><?= icon('mail') ?> <?= e(setting('email')) ?></p><?php endif; ?>
            <?php if (setting('whatsapp')): ?><p><a href="https://wa.me/<?= e(preg_replace('/^0/', '98', en_num(setting('whatsapp')))) ?>" target="_blank" rel="noopener"><?= icon('message-square') ?> گفتگوی واتساپ</a></p><?php endif; ?>
            <p>
                <?php if (setting('instagram')): ?><a href="https://instagram.com/<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">اینستاگرام</a><?php endif; ?>
                <?php if (setting('telegram')): ?> | <a href="https://t.me/<?= e(setting('telegram')) ?>" target="_blank" rel="noopener">تلگرام</a><?php endif; ?>
            </p>
        </div>
    </div>
    <div class="wrap copy">© <?= jdate('Y') ?> — <?= e(setting('footer_note')) ?></div>
</footer>
<script src="assets/js/app.js"></script>
</body>
</html>
