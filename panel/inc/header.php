<?php
/** هدر پنل — دسترسی فقط برای staff */
if (!defined('PARCHE')) exit;
if (!current_user() || !is_staff()) redirect('login.php');
$u = current_user();
$isAdmin = is_admin();
$unread = unread_messages_count($u['id']);
$pendingReviews = $isAdmin ? (int)qv("SELECT COUNT(*) FROM reviews WHERE status='pending'") : (int)qv("SELECT COUNT(*) FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.status='pending' AND p.seller_id = ?", [$u['id']]);
$pendingOrders = is_seller()
    ? (int)qv("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON oi.order_id = o.id WHERE oi.seller_id = ? AND o.status = 'pending'", [$u['id']])
    : (int)qv("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$cur = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>پنل مدیریت <?= e(setting('site_name', 'پارچه‌سرا')) ?></title>
<meta name="robots" content="noindex, nofollow">
<?= font_head_tags() ?>
<link rel="stylesheet" href="../assets/css/panel.css">
</head>
<body>
<div class="panel">
    <aside class="p-side">
        <div class="p-brand"><?= icon('scissors') ?> <b>پنل <?= $isAdmin ? 'مدیریت' : 'فروشنده' ?></b></div>
        <nav>
            <a class="<?= $cur === 'index.php' ? 'on' : '' ?>" href="index.php"><?= icon('layout-dashboard') ?> داشبورد</a>
            <?php if ($isAdmin): ?>
            <a class="<?= $cur === 'categories.php' ? 'on' : '' ?>" href="categories.php"><?= icon('folder-tree') ?> دسته‌بندی‌ها</a>
            <?php endif; ?>
            <a class="<?= $cur === 'products.php' ? 'on' : '' ?>" href="products.php"><?= icon('shirt') ?> محصولات
                <?php $pc = is_seller() ? (int)qv("SELECT COUNT(*) FROM products WHERE seller_id=?", [$u['id']]) : (int)qv("SELECT COUNT(*) FROM products"); ?>
                <span class="pill"><?= fa_num($pc) ?></span>
            </a>
            <a class="<?= $cur === 'inventory.php' ? 'on' : '' ?>" href="inventory.php"><?= icon('package') ?> انبار و موجودی</a>
            <a class="<?= $cur === 'orders.php' ? 'on' : '' ?>" href="orders.php"><?= icon('clipboard-list') ?> سفارش‌ها
                <?php if ($pendingOrders): ?><span class="pill warn"><?= fa_num($pendingOrders) ?></span><?php endif; ?>
            </a>
            <a class="<?= $cur === 'invoices.php' ? 'on' : '' ?>" href="invoices.php"><?= icon('receipt') ?> فاکتورها</a>
            <?php if ($isAdmin): ?>
            <a class="<?= $cur === 'customers.php' ? 'on' : '' ?>" href="customers.php"><?= icon('users') ?> مشتریان</a>
            <a class="<?= $cur === 'sellers.php' ? 'on' : '' ?>" href="sellers.php"><?= icon('store') ?> فروشندگان</a>
            <a class="<?= $cur === 'coupons.php' ? 'on' : '' ?>" href="coupons.php"><?= icon('ticket') ?> کدهای تخفیف</a>
            <?php endif; ?>
            <a class="<?= $cur === 'reviews.php' ? 'on' : '' ?>" href="reviews.php"><?= icon('star') ?> نظرات
                <?php if ($pendingReviews): ?><span class="pill warn"><?= fa_num($pendingReviews) ?></span><?php endif; ?>
            </a>
            <a class="<?= $cur === 'messages.php' ? 'on' : '' ?>" href="messages.php"><?= icon('message-square') ?> پیام‌ها
                <?php if ($unread): ?><span class="pill warn"><?= fa_num($unread) ?></span><?php endif; ?>
            </a>
            <?php if ($isAdmin): ?>
            <a class="<?= $cur === 'settings.php' ? 'on' : '' ?>" href="settings.php"><?= icon('settings') ?> تنظیمات</a>
            <?php endif; ?>
            <a href="../index.php" target="_blank"><?= icon('globe') ?> مشاهده سایت</a>
        </nav>
        <div class="p-side-foot">
            <div class="p-user">
                <div class="avatar"><?= e(mb_substr($u['name'], 0, 1)) ?></div>
                <div><b><?= e($u['name']) ?></b><br><small class="muted"><?= $isAdmin ? 'مدیر سیستم' : e($u['shop_name'] ?: 'فروشنده') ?></small></div>
            </div>
            <a class="logout" href="login.php?logout=1">خروج ⏻</a>
        </div>
    </aside>
    <div class="p-main">
        <header class="p-top">
            <h1><?= isset($pageTitle) ? e($pageTitle) : 'پنل' ?></h1>
            <div class="p-top-links">
                <span class="muted"><?= jdate('Y/m/d') ?></span>
            </div>
        </header>
        <div class="p-content">
        <?php foreach (flash_get() as $f): ?>
            <div class="p-alert p-alert-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
        <?php endforeach; ?>
