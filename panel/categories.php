<?php
require_once __DIR__ . '/../app/init.php';
if (!is_admin()) { flash_set('e', 'فقط مدیر به دسته‌بندی‌ها دسترسی دارد.'); redirect('index.php'); }
$pageTitle = 'دسته‌بندی‌ها';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;
        $desc = trim($_POST['description'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $balance = (int)($_POST['balance'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if (mb_strlen($name) < 2) {
            flash_set('e', 'نام دسته حداقل ۲ کاراکتر.');
        } elseif ($parentId && $id && $parentId === $id) {
            flash_set('e', 'دسته نمی‌تواند والد خودش باشد.');
        } else {
            if ($id) {
                q("UPDATE categories SET parent_id=?, name=?, description=?, sort_order=?, balance=?, is_active=? WHERE id=?",
                  [$parentId, $name, $desc ?: null, $sort, $balance, $active, $id]);
                flash_set('s', 'دسته «' . $name . '» ویرایش شد.');
            } else {
                q("INSERT INTO categories (parent_id, name, description, sort_order, balance, is_active, created_at) VALUES (?,?,?,?,?,?,?)",
                  [$parentId, $name, $desc ?: null, $sort, $balance, $active, now()]);
                flash_set('s', 'دسته «' . $name . '» ایجاد شد.');
            }
        }
        redirect('categories.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $kids = (int)qv("SELECT COUNT(*) FROM categories WHERE parent_id = ?", [$id]);
        $prods = (int)qv("SELECT COUNT(*) FROM products WHERE category_id = ?", [$id]);
        if ($kids || $prods) {
            flash_set('e', 'این دسته ' . ($kids ? 'زیرشاخه دارد' : '') . ($kids && $prods ? ' و ' : '') . ($prods ? 'محصول دارد' : '') . '؛ اول آن‌ها را منتقل کنید.');
        } else {
            q("DELETE FROM categories WHERE id = ?", [$id]);
            flash_set('s', 'دسته حذف شد.');
        }
        redirect('categories.php');
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        q("UPDATE categories SET is_active = 1 - is_active WHERE id = ?", [$id]);
        redirect('categories.php');
    }
}

$edit = null;
if (!empty($_GET['edit'])) $edit = q1("SELECT * FROM categories WHERE id = ?", [(int)$_GET['edit']]);

include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <h3><?= $edit ? 'ویرایش دسته: ' . e($edit['name']) : icon('plus') . ' افزودن دسته جدید' ?></h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>نام دسته *
                <input name="name" required value="<?= e($edit['name'] ?? '') ?>" placeholder="مثلاً پارچه نخی">
            </label>
            <label>والد (برای ساخت درخت)
                <select name="parent_id">
                    <option value="0">— دسته اصلی (بدون والد) —</option>
                    <?php foreach (q("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll() as $c): if ($edit && (int)$c['id'] === (int)$edit['id']) continue; ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($edit && (int)($edit['parent_id'] ?: 0) === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= e($c['name']) ?><?= $c['parent_id'] ? ' (زیرمجموعه)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>توضیحات
                <textarea name="description" rows="2"><?= e($edit['description'] ?? '') ?></textarea>
            </label>
            <div class="row3">
                <label>ترتیب نمایش<input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></label>
                <label>بالانس (وزن نمایش/فروش هوشمند)<input type="number" name="balance" value="<?= (int)($edit['balance'] ?? 0) ?>"></label>
                <label class="chk"><input type="checkbox" name="is_active" <?= (!$edit || (int)$edit['is_active']) ? 'checked' : '' ?>> فعال</label>
            </div>
            <button class="btn btn-primary" type="submit"><?= icon('save') ?> ذخیره</button>
            <?php if ($edit): ?><a class="btn btn-ghost" href="categories.php">انصراف</a><?php endif; ?>
        </form>
        <div class="p-note"><?= icon('lightbulb') ?> فیلد «بالانس» پایه‌ی سیستم بالانس‌سازی و طرح فروش هوشمند فاز بعدی است: عدد بزرگ‌تر = نمایش و اولویت بیشتر.</div>
    </div>

    <div class="p-box">
        <h3>درخت دسته‌بندی</h3>
        <?php
        $renderTree = function ($nodes, $depth = 0) use (&$renderTree) {
            foreach ($nodes as $n) {
                $prodCount = (int)qv("SELECT COUNT(*) FROM products WHERE category_id = ?", [$n['id']]);
                echo '<div class="cat-row" style="margin-inline-start:' . ($depth * 22) . 'px">';
                echo '<span class="cat-toggle">' . ($n['children'] ? '▾' : '•') . '</span>';
                echo '<b>' . e($n['name']) . '</b>';
                echo '<span class="pill">' . fa_num($prodCount) . ' محصول</span>';
                if (!$n['is_active']) echo '<span class="tag tag-r">غیرفعال</span>';
                if ((int)$n['balance'] !== 0) echo '<span class="tag tag-b">بالانس ' . fa_num($n['balance']) . '</span>';
                echo '<span class="spacer"></span>';
                echo '<a class="btn btn-sm btn-ghost" href="categories.php?edit=' . (int)$n['id'] . '">ویرایش</a>';
                echo '<form method="post" style="display:inline" onsubmit="return confirm(\'حذف شود؟\')">' . csrf_field() .
                     '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . (int)$n['id'] . '">' .
                     '<button class="btn btn-sm btn-danger">حذف</button></form>';
                echo '</div>';
                if ($n['children']) $renderTree($n['children'], $depth + 1);
            }
        };
        echo '<div class="cat-tree">';
        $renderTree(category_tree(false));
        echo '</div>';
        ?>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
