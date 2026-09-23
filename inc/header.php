<?php
/** هدر مشترک سایت */
if (!defined('PARCHE')) exit;
$u = current_user();
$cartCount = cart_count();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e(setting('site_name', 'پارچینو')) ?></title>
<meta name="description" content="<?= e(setting('site_tagline', 'فروشگاه اینترنتی پارچه')) ?>">
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%232b8fd6'%20stroke-width='2'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Ccircle%20cx='6'%20cy='6'%20r='3'/%3E%3Ccircle%20cx='6'%20cy='18'%20r='3'/%3E%3Cline%20x1='20'%20y1='4'%20x2='8.12'%20y2='15.88'/%3E%3Cline%20x1='14.47'%20y1='14.48'%20x2='20'%20y2='20'/%3E%3Cline%20x1='8.12'%20y1='8.12'%20x2='12'%20y2='12'/%3E%3C/svg%3E">
<?= font_head_tags() ?>
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <div class="wrap">
            <div class="topbar-right">
                <?php if (setting('mobile')): ?><span class="tb-item"><?= icon('smartphone') ?> <?= fa_num(en_num(setting('mobile'))) ?></span><?php endif; ?>
                <span class="tb-item tb-ok"><?= icon('badge-check') ?> ضمانت کیفیت و مرجوعی ۷ روزه</span>
            </div>
            <div class="topbar-left">
                <?php if ($u): ?>
                    <a href="account.php"><?= icon('users') ?> حساب من (<?= e($u['name']) ?>)<?= ($c = unread_messages_count($u['id'])) ? ' <b class="badge-msg">' . fa_num($c) . '</b>' : '' ?></a>
                    <span class="sep"></span>
                    <a href="logout.php"><?= icon('key-round') ?> خروج</a>
                <?php else: ?>
                    <a href="login.php"><?= icon('users') ?> ورود</a>
                    <span class="sep"></span>
                    <a href="register.php"><?= icon('plus') ?> ثبت‌نام</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="mainbar">
        <div class="wrap mainbar-in">
            <a class="brand" href="index.php">
                <span class="logo"><?= icon('scissors') ?></span>
                <span class="brand-txt"><b><?= e(setting('site_name', 'پارچینو')) ?></b><small><?= e(setting('site_tagline')) ?></small></span>
            </a>
            <form class="searchbox" action="search.php" method="get">
                <?= icon('search', 'sb-ico') ?>
                <input type="text" name="q" placeholder="جستجو در بین پارچه‌ها… مثلاً ترگال، ساتن، نخی" value="<?= e($_GET['q'] ?? '') ?>">
                <button type="submit">جستجو</button>
            </form>
            <a class="cartbtn" href="cart.php">
                <span class="cart-ico"><?= icon('shopping-cart') ?></span>
                <span class="cart-txt"><small>سبد خرید</small><b><?= $cartCount ? fa_num($cartCount) . ' قلم کالا' : 'خالی' ?></b></span>
                <?php if ($cartCount): ?><span class="cart-badge"><?= fa_num($cartCount) ?></span><?php endif; ?>
            </a>
        </div>
    </div>
    <nav class="catnav">
        <div class="wrap">
            <a href="index.php" class="cat-link <?= (basename($_SERVER['SCRIPT_NAME']) === 'index.php') ? 'on' : '' ?>">صفحه اصلی</a>
            <?php
            $tree = category_tree();
            foreach ($tree as $top):
                if (!$top['children']) { ?>
                    <a class="cat-link" href="category.php?id=<?= (int)$top['id'] ?>"><?= e($top['name']) ?></a>
                <?php } else { ?>
                <div class="cat-drop">
                    <a class="cat-link" href="category.php?id=<?= (int)$top['id'] ?>"><?= e($top['name']) ?> <?= icon('chevron-down', 'chev') ?></a>
                    <div class="drop-panel">
                        <?php foreach ($top['children'] as $ch): ?>
                            <a href="category.php?id=<?= (int)$ch['id'] ?>"><?= e($ch['name']) ?><span class="dp-count"><?= fa_num((int)qv("SELECT COUNT(*) FROM products WHERE category_id = ? AND status='active'", [$ch['id']])) ?></span></a>
                        <?php endforeach; ?>
                        <a class="all" href="category.php?id=<?= (int)$top['id'] ?>">مشاهده همه <?= e($top['name']) ?> <?= icon('arrow-left') ?></a>
                    </div>
                </div>
                <?php } ?>
            <?php endforeach; ?>
            <span class="cat-spacer"></span>
            <a href="about.php" class="cat-link">درباره ما</a>
            <a href="track.php" class="cat-link">پیگیری سفارش</a>
        </div>
    </nav>
</header>

<main class="site-main">
<?php foreach (flash_get() as $f): ?>
    <div class="wrap"><div class="alert alert-<?= e($f['t']) ?>"><?= icon($f['t'] === 's' ? 'circle-check' : 'triangle-alert') ?> <?= e($f['m']) ?></div></div>
<?php endforeach; ?>
