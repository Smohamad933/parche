<?php
require_once __DIR__ . '/../app/init.php';
if (!is_admin()) { flash_set('e', 'دسترسی فقط برای مدیر.'); redirect('index.php'); }
$pageTitle = 'کدهای تخفیف';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $code = strtoupper(trim(en_num($_POST['code'] ?? '')));
    $type = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
    $value = (float)en_num($_POST['value'] ?? 0);
    $min = (float)en_num($_POST['min_amount'] ?? 0);
    $maxUses = (int)($_POST['max_uses'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($code === '' || $value <= 0) {
        flash_set('e', 'کد و مقدار تخفیف لازم است.');
    } elseif ($id) {
        q("UPDATE coupons SET code=?, type=?, value=?, min_amount=?, max_uses=?, is_active=? WHERE id=?", [$code, $type, $value, $min, $maxUses, $active, $id]);
        flash_set('s', 'کد تخفیف ویرایش شد.');
    } else {
        if (q1("SELECT id FROM coupons WHERE code = ?", [$code])) {
            flash_set('e', 'این کد قبلاً وجود دارد.');
        } else {
            q("INSERT INTO coupons (code, type, value, min_amount, max_uses, used_count, expires_at, is_active) VALUES (?,?,?,?,?,0,NULL,?)",
              [$code, $type, $value, $min, $maxUses, $active]);
            flash_set('s', 'کد تخفیف ایجاد شد.');
        }
    }
    redirect('coupons.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    q("DELETE FROM coupons WHERE id = ?", [(int)($_POST['id'] ?? 0)]);
    flash_set('s', 'کد حذف شد.');
    redirect('coupons.php');
}

$rows = q("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
include __DIR__ . '/inc/header.php';
?>
<div class="cols-2">
    <div class="p-box">
        <h3><?= icon('plus') ?> کد تخفیف جدید</h3>
        <form method="post" class="p-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <div class="row2">
                <label>کد (لاتین) *<input name="code" dir="ltr" required placeholder="SUMMER20"></label>
                <label>نوع
                    <select name="type">
                        <option value="percent">درصدی</option>
                        <option value="fixed">مبلغ ثابت (تومان)</option>
                    </select>
                </label>
            </div>
            <div class="row2">
                <label>مقدار *<input name="value" dir="ltr" required placeholder="10 یا 50000"></label>
                <label>حداقل مبلغ سفارش<input name="min_amount" dir="ltr" placeholder="0"></label>
            </div>
            <div class="row2">
                <label>سقف تعداد استفاده (۰ = بی‌نهایت)<input name="max_uses" dir="ltr" value="0"></label>
                <label class="chk"><input type="checkbox" name="is_active" checked> فعال</label>
            </div>
            <button class="btn btn-primary" type="submit">ایجاد کد</button>
        </form>
    </div>
    <div class="p-box">
        <h3>کدهای فعال</h3>
        <table class="p-table">
            <thead><tr><th>کد</th><th>نوع</th><th>مقدار</th><th>حداقل</th><th>استفاده</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $c): ?>
                <tr>
                    <td dir="ltr"><b><?= e($c['code']) ?></b></td>
                    <td><?= $c['type'] === 'percent' ? 'درصدی' : 'مبلغی' ?></td>
                    <td><?= $c['type'] === 'percent' ? fa_num($c['value']) . '٪' : price($c['value']) ?></td>
                    <td><?= price($c['min_amount']) ?></td>
                    <td><?= fa_num($c['used_count']) ?>/<?= (int)$c['max_uses'] ? fa_num($c['max_uses']) : '∞' ?></td>
                    <td><span class="tag tag-<?= (int)$c['is_active'] ? 'g' : 'r' ?>"><?= (int)$c['is_active'] ? 'فعال' : 'خاموش' ?></span></td>
                    <td>
                        <form method="post" onsubmit="return confirm('حذف شود؟')"><?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-sm btn-danger">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/inc/footer.php'; ?>
