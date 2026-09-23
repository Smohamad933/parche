<?php if (!defined('PARCHE')) exit; /** کارت محصول — متغیر $p */ ?>
<a class="card" href="product.php?id=<?= (int)$p['id'] ?>">
    <div class="card-img">
        <img loading="lazy" src="<?= product_image($p['img'] ?? null) ?>" alt="<?= e($p['name']) ?>">
        <?php if (!empty($p['old_price']) && (float)$p['old_price'] > (float)$p['price']): ?>
            <span class="off"><?= fa_num(round(((float)$p['old_price'] - (float)$p['price']) / (float)$p['old_price'] * 100)) ?>٪</span>
        <?php endif; ?>
        <?php if ((float)$p['stock'] <= 0): ?><span class="oos">ناموجود</span>
        <?php elseif ((float)$p['stock'] <= (float)$p['low_stock']): ?><span class="low">آخرین موجودی</span><?php endif; ?>
    </div>
    <div class="card-body">
        <h3><?= e($p['name']) ?></h3>
        <div class="card-meta">
            <span>عرض <?= fa_num($p['width_cm'] ?: '—') ?> سانت</span>
            <span><?= e($p['color'] ?: '') ?></span>
        </div>
        <div class="card-price">
            <?php if (!empty($p['old_price']) && (float)$p['old_price'] > (float)$p['price']): ?>
                <del><?= fa_num(number_format((float)$p['old_price'])) ?></del>
            <?php endif; ?>
            <b><?= fa_num(number_format((float)$p['price'])) ?></b> <i>تومان/متر</i>
        </div>
    </div>
</a>
