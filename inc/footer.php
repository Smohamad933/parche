<?php if (!defined('PARCHE')) exit; ?>
</main>
<footer class="site-footer">
    <div class="wrap cols">
        <div>
            <h4><?= icon('scissors') ?> <?= e(setting('site_name', 'پارچه‌سرا')) ?></h4>
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
            <p><?= icon('map-pin') ?> <?= e(setting('address')) ?></p>
            <p><?= icon('phone') ?> <?= e(setting('phone')) ?> — <?= e(setting('mobile')) ?></p>
            <p><?= icon('mail') ?> <?= e(setting('email')) ?></p>
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
