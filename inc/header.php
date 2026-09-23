<?php
/** هدر مشترک سایت */
if (!defined('PARCHE')) exit;
$u = current_user();
$cartCount = cart_count();
$tree = function_exists('category_tree') ? null : null;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e(setting('site_name', 'پارچه‌سرا')) ?></title>
<meta name="description" content="<?= e(setting('site_tagline', 'فروشگاه جامع پارچه')) ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🧵</text></svg>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
<link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<header class="site-header">
    <div class="topbar">
        <div class="wrap">
            <span>☎ <?= e(setting('phone')) ?></span>
            <span class="sep"></span>
            <span>ارسال رایگان برای خرید بالای <?= price(setting('free_shipping_min', 3000000)) ?></span>
            <span class="spacer"></span>
            <?php if ($u): ?>
                <a href="account.php">حساب من (<?= e($u['name']) ?>)<?= ($c = unread_messages_count($u['id'])) ? ' <b class="badge-msg">' . fa_num($c) . '</b>' : '' ?></a>
                <span class="sep"></span>
                <a href="logout.php">خروج</a>
            <?php else: ?>
                <a href="login.php">ورود</a>
                <span class="sep"></span>
                <a href="register.php">ثبت‌نام</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="mainbar wrap">
        <a class="brand" href="index.php"><span class="logo">🧵</span> <b><?= e(setting('site_name', 'پارچه‌سرا')) ?></b></a>
        <form class="searchbox" action="search.php" method="get">
            <input type="text" name="q" placeholder="جستجوی پارچه… (مثلاً ترگال، ساتن، نخی)" value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit">🔍</button>
        </form>
        <a class="cartbtn" href="cart.php">🛒 سبد خرید<?= $cartCount ? ' <span class="cart-badge">' . fa_num($cartCount) . '</span>' : '' ?></a>
    </div>
    <nav class="catnav">
        <div class="wrap">
            <a href="index.php" class="cat-link">خانه</a>
            <?php
            $tree = category_tree();
            foreach ($tree as $top):
                if (!$top['children']) { ?>
                    <a class="cat-link" href="category.php?id=<?= (int)$top['id'] ?>"><?= e($top['name']) ?></a>
                <?php } else { ?>
                <div class="cat-drop">
                    <a class="cat-link" href="category.php?id=<?= (int)$top['id'] ?>"><?= e($top['name']) ?> ▾</a>
                    <div class="drop-panel">
                        <?php foreach ($top['children'] as $ch): ?>
                            <a href="category.php?id=<?= (int)$ch['id'] ?>"><?= e($ch['name']) ?></a>
                        <?php endforeach; ?>
                        <a class="all" href="category.php?id=<?= (int)$top['id'] ?>">همه <?= e($top['name']) ?> ←</a>
                    </div>
                </div>
                <?php } ?>
            <?php endforeach; ?>
            <a href="about.php" class="cat-link">درباره ما</a>
            <a href="track.php" class="cat-link">پیگیری سفارش</a>
        </div>
    </nav>
</header>

<main class="wrap main-content">
<?php foreach (flash_get() as $f): ?>
    <div class="alert alert-<?= e($f['t']) ?>"><?= e($f['m']) ?></div>
<?php endforeach; ?>
