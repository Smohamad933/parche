<?php
require_once __DIR__ . '/../app/init.php';
$pageTitle = 'محصولات';
$isAdmin = is_admin();
$uid = current_user()['id'];
$scope = $isAdmin ? '' : ' AND seller_id = ' . $uid;

/* حذف */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $p = q1("SELECT * FROM products WHERE id = ? AND (1=1 $scope)", [$id]);
    if ($p) { q("DELETE FROM products WHERE id = ?", [$id]); flash_set('s', 'محصول حذف شد.'); }
    redirect('products.php');
}
/* تغییر وضعیت/پیشنهادی */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['toggle', 'feature'], true)) {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['action'] === 'toggle') q("UPDATE products SET status = CASE status WHEN 'active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND (1=1 $scope)", [$id]);
    else q("UPDATE products SET is_featured = 1 - is_featured WHERE id = ? AND (1=1 $scope)", [$id]);
    redirect('products.php');
}

/* ذخیره محصول (افزودن/ویرایش) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $f = [
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
        'seller_id' => $isAdmin ? ((int)($_POST['seller_id'] ?? 0) ?: null) : $uid,
        'name' => trim($_POST['name'] ?? ''),
        'code' => trim($_POST['code'] ?? '') ?: null,
        'description' => trim($_POST['description'] ?? ''),
        'price' => (float)en_num($_POST['price'] ?? 0),
        'old_price' => (float)en_num($_POST['old_price'] ?? 0) ?: null,
        'unit' => trim($_POST['unit'] ?? 'متر') ?: 'متر',
        'stock' => (float)en_num($_POST['stock'] ?? 0),
        'low_stock' => (int)($_POST['low_stock'] ?? 5),
        'min_order' => (float)en_num($_POST['min_order'] ?? 0.5) ?: 0.5,
        'width_cm' => (int)($_POST['width_cm'] ?? 0) ?: null,
        'material' => trim($_POST['material'] ?? '') ?: null,
        'color' => trim($_POST['color'] ?? '') ?: null,
        'pattern' => trim($_POST['pattern'] ?? '') ?: null,
        'origin' => trim($_POST['origin'] ?? '') ?: null,
        'status' => ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
    ];
    if (mb_strlen($f['name']) < 3) {
        flash_set('e', 'نام محصول حداقل ۳ کاراکتر.');
    } elseif ($id) {
        $old = q1("SELECT stock FROM products WHERE id = ? AND (1=1 $scope)", [$id]);
        if (!$old) { flash_set('e', 'محصول یافت نشد.'); redirect('products.php'); }
        q("UPDATE products SET category_id=?, seller_id=?, name=?, code=?, description=?, price=?, old_price=?, unit=?, stock=?, low_stock=?, min_order=?, width_cm=?, material=?, color=?, pattern=?, origin=?, status=?, is_featured=? WHERE id=?",
          array_merge(array_values($f), [$id]));
        if (abs((float)$old['stock'] - $f['stock']) > 0.001) {
            q("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, stock_after, created_at) VALUES (?,?,?,'adjust','اصلاح موجودی از فرم محصول',?,?,?)",
              [$id, $uid, $f['stock'] - (float)$old['stock'], $f['stock'], now()]);
        }
        flash_set('s', 'محصول ذخیره شد.');
    } else {
        q("INSERT INTO products (category_id, seller_id, name, code, description, price, old_price, unit, stock, low_stock, min_order, width_cm, material, color, pattern, origin, status, is_featured, views, created_at)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?)", array_merge(array_values($f), [now()]));
        $id = last_id();
        if (!$f['code']) q("UPDATE products SET code = ? WHERE id = ?", ['PRC-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT), $id]);
        if ($f['stock'] > 0) {
            q("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, stock_after, created_at) VALUES (?,?,?,'in','موجودی اولیه',?,?,?)",
              [$id, $uid, $f['stock'], $f['stock'], now()]);
        }
        flash_set('s', 'محصول جدید ایجاد شد. اکنون می‌توانید تصویر اضافه کنید.');
    }

    /* آپلود تصاویر */
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $dir = __DIR__ . '/../uploads/products/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $n = count($_FILES['images']['name']);
        $maxSort = (int)qv("SELECT COALESCE(MAX(sort_order),0) FROM product_images WHERE product_id = ?", [$id]);
        for ($i = 0; $i < $n; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $tmp = $_FILES['images']['tmp_name'][$i];
            $info = @getimagesize($tmp);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) {
                flash_set('e', 'فایل تصویر شماره ' . ($i + 1) . ' معتبر نیست (فقط jpg/png/webp).');
                continue;
            }
            $ext = image_type_to_extension($info[2], false);
            $fname = 'p' . $id . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($tmp, $dir . $fname)) {
                q("INSERT INTO product_images (product_id, path, sort_order) VALUES (?,?,?)", [$id, 'uploads/products/' . $fname, ++$maxSort]);
            }
        }
    }
    redirect('products.php?edit=' . $id);
}

/* حذف تصویر */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delimg') {
    csrf_verify();
    $imgId = (int)($_POST['img_id'] ?? 0);
    $img = q1("SELECT i.*, p.seller_id FROM product_images i JOIN products p ON p.id = i.product_id WHERE i.id = ? AND (1=1 $scope)", [$imgId]);
    if ($img) {
        @unlink(__DIR__ . '/../' . $img['path']);
        q("DELETE FROM product_images WHERE id = ?", [$imgId]);
        flash_set('s', 'تصویر حذف شد.');
    }
    redirect('products.php?edit=' . (int)($_POST['product_id'] ?? 0));
}

/* لیست */
$flter = trim($_GET['q'] ?? '');
$pcat = (int)($_GET['cat'] ?? 0);
$where = '1=1' . $scope;
$params = [];
if ($flter !== '') { $where .= ' AND (name LIKE ? OR code LIKE ?)'; $params[] = "%$flter%"; $params[] = "%$flter%"; }
if ($pcat) { $ids = category_children_flat($pcat, false); $where .= ' AND category_id IN (' . in_ph($ids) . ')'; $params = array_merge($params, $ids); }
$per = 15;
$page = max(1, (int)($_GET['p'] ?? 1));
$total = (int)qv("SELECT COUNT(*) FROM products WHERE $where", $params);
$rows = q("SELECT p.*, c.name AS cat_name, u.shop_name,
           (SELECT path FROM product_images i WHERE i.product_id = p.id ORDER BY sort_order LIMIT 1) AS img
           FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.seller_id
           WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $params)->fetchAll();
$pages = max(1, (int)ceil($total / $per));

$edit = !empty($_GET['edit']) ? q1("SELECT * FROM products WHERE id = ? AND (1=1 $scope)", [(int)$_GET['edit']]) : null;
$editImgs = $edit ? q("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order", [$edit['id']])->fetchAll() : [];

include __DIR__ . '/inc/header.php';
?>
<div class="p-box">
    <form class="p-filter" method="get">
        <input name="q" placeholder="جستجوی نام یا کد…" value="<?= e($flter) ?>">
        <select name="cat">
            <option value="0">همه دسته‌ها</option>
            <?php foreach (q("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll() as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $pcat === (int)$c['id'] ? 'selected' : '' ?>><?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">فیلتر</button>
        <span class="spacer"></span>
        <a class="btn btn-primary" href="products.php?edit=0"><?= icon('plus') ?> محصول جدید</a>
    </form>

    <table class="p-table">
        <thead><tr><th>تصویر</th><th>نام / کد</th><th>دسته</th><th>قیمت (تومان)</th><th>موجودی</th><th>فروشنده</th><th>وضعیت</th><th>عملیات</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $p): ?>
        <tr class="<?= (float)$p['stock'] <= (float)$p['low_stock'] ? 'row-warn' : '' ?>">
            <td><img class="thumb" src="<?= product_image($p['img']) ?>" alt=""></td>
            <td><b><?= e($p['name']) ?></b><br><small class="muted" dir="ltr"><?= e($p['code']) ?></small></td>
            <td><?= e($p['cat_name'] ?: '—') ?></td>
            <td><?= fa_num(number_format((float)$p['price'])) ?>
                <?php if (!empty($p['old_price'])): ?><br><del class="muted"><small><?= fa_num(number_format((float)$p['old_price'])) ?></small></del><?php endif; ?>
            </td>
            <td><b><?= fa_num(rtrim(rtrim((string)$p['stock'], '0'), '.')) ?></b> متر</td>
            <td><?= e($p['shop_name'] ?: 'فروشگاه') ?></td>
            <td>
                <form method="post" style="display:inline"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="tag tag-<?= $p['status'] === 'active' ? 'g' : 'r' ?>" title="تغییر وضعیت"><?= $p['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></button>
                </form>
                <form method="post" style="display:inline"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="feature"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="tag tag-<?= (int)$p['is_featured'] ? 'b' : 'w' ?>" title="پیشنهادی صفحه اصلی"><?= (int)$p['is_featured'] ? icon('star','st-on') . ' پیشنهادی' : 'معمولی' ?></button>
                </form>
            </td>
            <td>
                <a class="btn btn-sm btn-ghost" href="products.php?edit=<?= (int)$p['id'] ?>">ویرایش</a>
                <a class="btn btn-sm btn-ghost" href="../product.php?id=<?= (int)$p['id'] ?>" target="_blank">مشاهده</a>
                <form method="post" style="display:inline" onsubmit="return confirm('محصول حذف شود؟')"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-danger">حذف</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <nav class="pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'on' : '' ?>" href="products.php?q=<?= urlencode($flter) ?>&cat=<?= $pcat ?>&p=<?= $i ?>"><?= fa_num($i) ?></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
</div>

<?php if ($edit !== null || isset($_GET['edit']) && $_GET['edit'] === '0'): ?>
<div class="p-box" id="form">
    <h3><?= $edit ? icon('pencil') . ' ویرایش محصول: ' . e($edit['name']) : icon('plus') . ' افزودن محصول جدید' ?></h3>
    <form method="post" enctype="multipart/form-data" class="p-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <div class="row2">
            <label>نام محصول *<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
            <label>کد محصول<input name="code" dir="ltr" value="<?= e($edit['code'] ?? '') ?>" placeholder="خالی = خودکار"></label>
        </div>
        <div class="row2">
            <label>دسته‌بندی *
                <select name="category_id" required>
                    <option value="">— انتخاب دسته —</option>
                    <?php foreach (q("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll() as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $edit && (int)$edit['category_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= $c['parent_id'] ? '— ' : '' ?><?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($isAdmin): ?>
            <label>فروشنده
                <select name="seller_id">
                    <option value="0">— فروشگاه (مدیر) —</option>
                    <?php foreach (q("SELECT id, shop_name, name FROM users WHERE role='seller'")->fetchAll() as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= $edit && (int)$edit['seller_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['shop_name'] ?: $s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </div>
        <label>توضیحات<textarea name="description" rows="4"><?= e($edit['description'] ?? '') ?></textarea></label>
        <div class="row3">
            <label>قیمت هر متر (تومان) *<input name="price" required dir="ltr" value="<?= e((string)($edit['price'] ?? '')) ?>"></label>
            <label>قیمت قبل از تخفیف<input name="old_price" dir="ltr" value="<?= e(isset($edit['old_price']) && $edit['old_price'] !== null ? (string)$edit['old_price'] : '') ?>"></label>
            <label>واحد<input name="unit" value="<?= e($edit['unit'] ?? 'متر') ?>"></label>
        </div>
        <div class="row3">
            <label>موجودی (متر)<input name="stock" dir="ltr" value="<?= e(rtrim(rtrim((string)($edit['stock'] ?? '0'), '0'), '.')) ?>"></label>
            <label>حد هشدار کمبود<input name="low_stock" dir="ltr" value="<?= e((string)($edit['low_stock'] ?? '5')) ?>"></label>
            <label>حداقل سفارش (متر)<input name="min_order" dir="ltr" value="<?= e(rtrim(rtrim((string)($edit['min_order'] ?? '0.5'), '0'), '.')) ?>"></label>
        </div>
        <div class="row3">
            <label>عرض (سانت)<input name="width_cm" dir="ltr" value="<?= e((string)($edit['width_cm'] ?? '')) ?>"></label>
            <label>جنس<input name="material" value="<?= e($edit['material'] ?? '') ?>" placeholder="مثلاً ۱۰۰٪ پنبه"></label>
            <label>رنگ<input name="color" value="<?= e($edit['color'] ?? '') ?>"></label>
        </div>
        <div class="row3">
            <label>طرح<input name="pattern" value="<?= e($edit['pattern'] ?? '') ?>"></label>
            <label>مبدا<input name="origin" value="<?= e($edit['origin'] ?? '') ?>"></label>
            <label>وضعیت
                <select name="status">
                    <option value="active" <?= !$edit || $edit['status'] === 'active' ? 'selected' : '' ?>>فعال</option>
                    <option value="inactive" <?= $edit && $edit['status'] === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
                </select>
            </label>
        </div>
        <label class="chk"><input type="checkbox" name="is_featured" <?= $edit && (int)$edit['is_featured'] ? 'checked' : '' ?>> نمایش در «پارچه‌های پیشنهادی» صفحه اصلی</label>
        <label>تصاویر (می‌توانید چند فایل انتخاب کنید)
            <input type="file" name="images[]" multiple accept="image/*">
        </label>
        <button class="btn btn-primary btn-lg" type="submit"><?= icon('save') ?> ذخیره محصول</button>
    </form>

    <?php if ($edit && $editImgs): ?>
    <h4>تصاویر فعلی</h4>
    <div class="img-grid">
        <?php foreach ($editImgs as $im): ?>
        <div class="img-item">
            <img src="../<?= e($im['path']) ?>" alt="">
            <form method="post" onsubmit="return confirm('حذف تصویر؟')"><?= csrf_field() ?>
                <input type="hidden" name="action" value="delimg">
                <input type="hidden" name="img_id" value="<?= (int)$im['id'] ?>">
                <input type="hidden" name="product_id" value="<?= (int)$edit['id'] ?>">
                <button class="btn btn-sm btn-danger">حذف</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
