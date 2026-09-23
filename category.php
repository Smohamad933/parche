<?php
require_once __DIR__ . '/app/init.php';

$catId = (int)($_GET['id'] ?? 0);
$cat = $catId ? q1("SELECT * FROM categories WHERE id = ?", [$catId]) : null;
if ($catId && !$cat) { flash_set('e', 'دسته‌بندی یافت نشد.'); redirect('category.php'); }

$sort = $_GET['sort'] ?? 'new';
$min = $_GET['min'] !== '' && isset($_GET['min']) ? (float)en_num($_GET['min']) : null;
$max = $_GET['max'] !== '' && isset($_GET['max']) ? (float)en_num($_GET['max']) : null;
$inStock = !empty($_GET['stock']);

$where = "p.status = 'active'";
$params = [];
if ($cat) {
    $ids = category_children_flat($cat['id']);
    $where .= " AND p.category_id IN (" . in_ph($ids) . ")";
    $params = array_merge($params, $ids);
}
if ($min !== null && $min >= 0) { $where .= " AND p.price >= ?"; $params[] = $min; }
if ($max !== null && $max > 0) { $where .= " AND p.price <= ?"; $params[] = $max; }
if ($inStock) { $where .= " AND p.stock > 0"; }

$orders = [
    'new' => 'p.id DESC',
    'cheap' => 'p.price ASC',
    'expensive' => 'p.price DESC',
    'popular' => 'p.views DESC, p.id DESC',
];
$order = $orders[$sort] ?? $orders['new'];

$per = 12;
$page = max(1, (int)($_GET['p'] ?? 1));
$total = (int)qv("SELECT COUNT(*) FROM products p WHERE $where", $params);
$rows = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
           FROM products p WHERE $where ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
$pages = max(1, (int)ceil($total / $per));

$pageTitle = $cat ? $cat['name'] : 'همه پارچه‌ها';
include __DIR__ . '/inc/header.php';
function qs_build($over = []) {
    $qs = array_merge($_GET, $over);
    return '?' . http_build_query($qs);
}
?>
<div class="breadcrumb"><a href="index.php">خانه</a> ‹ <a href="category.php">دسته‌بندی‌ها</a>
<?php if ($cat): foreach (category_path($cat['id']) as $cp): ?> ‹ <a href="category.php?id=<?= (int)$cp['id'] ?>"><?= e($cp['name']) ?></a><?php endforeach; endif; ?>
</div>

<div class="catalog">
    <aside class="side">
        <div class="side-box">
            <h4>دسته‌بندی‌ها</h4>
            <?php foreach (category_tree() as $top): ?>
                <div class="tree-top">
                    <a class="tree-link <?= ($cat && (int)$cat['id'] === (int)$top['id']) ? 'on' : '' ?>" href="category.php?id=<?= (int)$top['id'] ?>">
                        <?= e($top['name']) ?> <small>(<?= fa_num((int)qv("SELECT COUNT(*) FROM products WHERE category_id IN (" . in_ph(category_children_flat((int)$top['id'])) . ") AND status='active'", category_children_flat((int)$top['id']))) ?>)</small>
                    </a>
                    <?php if ($top['children']): ?><div class="tree-children">
                        <?php foreach ($top['children'] as $ch): ?>
                            <a class="tree-link <?= ($cat && (int)$cat['id'] === (int)$ch['id']) ? 'on' : '' ?>" href="category.php?id=<?= (int)$ch['id'] ?>">— <?= e($ch['name']) ?></a>
                        <?php endforeach; ?>
                    </div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <form class="side-box" method="get" action="category.php">
            <h4>فیلتر قیمت (تومان)</h4>
            <?php if ($catId): ?><input type="hidden" name="id" value="<?= $catId ?>"><?php endif; ?>
            <div class="row2">
                <input type="text" name="min" placeholder="از" value="<?= e($_GET['min'] ?? '') ?>" dir="ltr">
                <input type="text" name="max" placeholder="تا" value="<?= e($_GET['max'] ?? '') ?>" dir="ltr">
            </div>
            <label class="chk"><input type="checkbox" name="stock" value="1" <?= $inStock ? 'checked' : '' ?>> فقط کالاهای موجود</label>
            <button class="btn btn-primary btn-sm" type="submit">اعمال فیلتر</button>
        </form>
    </aside>

    <section class="content">
        <div class="list-head">
            <h1><?= $cat ? e($cat['name']) : 'همه پارچه‌ها' ?></h1>
            <?php if ($cat && $cat['description']): ?><p class="muted"><?= e($cat['description']) ?></p><?php endif; ?>
            <div class="sorts">
                <span>مرتب‌سازی:</span>
                <?php foreach (['new' => 'جدیدترین', 'popular' => 'پرفروش‌ترین', 'cheap' => 'ارزان‌ترین', 'expensive' => 'گران‌ترین'] as $k => $lbl): ?>
                    <a class="<?= $sort === $k ? 'on' : '' ?>" href="<?= e(qs_build(['sort' => $k, 'p' => 1])) ?>"><?= e($lbl) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$rows): ?>
            <div class="empty">موردی مطابق فیلترها پیدا نشد. <a href="category.php<?= $catId ? '?id=' . $catId : '' ?>">حذف فیلترها</a></div>
        <?php else: ?>
        <div class="grid">
            <?php foreach ($rows as $p) include __DIR__ . '/inc/product-card.php'; ?>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="pager">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i === $page ? 'on' : '' ?>" href="<?= e(qs_build(['p' => $i])) ?>"><?= fa_num($i) ?></a>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
