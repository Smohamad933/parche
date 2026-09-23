<?php
require_once __DIR__ . '/app/init.php';
$q = trim($_GET['q'] ?? '');
$pageTitle = 'جستجو';
$per = 12;
$page = max(1, (int)($_GET['p'] ?? 1));
$rows = [];
$total = 0;
if ($q !== '') {
    $like = '%' . $q . '%';
    $where = "p.status='active' AND (p.name LIKE ? OR p.description LIKE ? OR p.material LIKE ? OR p.color LIKE ? OR p.pattern LIKE ?)";
    $params = [$like, $like, $like, $like, $like];
    $total = (int)qv("SELECT COUNT(*) FROM products p WHERE $where", $params);
    $rows = q("SELECT p.*, (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
               FROM products p WHERE $where ORDER BY p.views DESC, p.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
}
$pages = max(1, (int)ceil($total / $per));
include __DIR__ . '/inc/header.php';
?>
<h1 class="page-title">نتایج جستجو برای «<?= e($q) ?>»</h1>
<?php if ($q === ''): ?>
    <div class="empty">عبارتی برای جستجو وارد کنید.</div>
<?php elseif (!$rows): ?>
    <div class="empty">نتیجه‌ای یافت نشد. <a href="category.php">مشاهده همه پارچه‌ها ←</a></div>
<?php else: ?>
    <p class="muted"><?= fa_num($total) ?> نتیجه پیدا شد.</p>
    <div class="grid">
        <?php foreach ($rows as $p) include __DIR__ . '/inc/product-card.php'; ?>
    </div>
    <?php if ($pages > 1): ?>
    <nav class="pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'on' : '' ?>" href="search.php?q=<?= urlencode($q) ?>&p=<?= $i ?>"><?= fa_num($i) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
