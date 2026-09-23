<?php
/**
 * توابع عمومی فروشگاه پارچه
 */
if (!defined('PARCHE')) exit;

require_once __DIR__ . '/icons.php';

/* ---------- خروجی امن ---------- */
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- اعداد فارسی ---------- */
function fa_num($s) {
    return strtr((string)$s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹',','=>'،']);
}
function en_num($s) {
    return strtr((string)$s, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','،'=>',','.'=>'.']);
}

/* ---------- قیمت ---------- */
function price($n) { return fa_num(number_format((float)$n)) . ' تومان'; }
function price_raw($n) { return number_format((float)$n); }

/* ---------- تاریخ شمسی ---------- */
function g2j($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $jy = ($gy <= 1600) ? 0 : 979;
    $gy -= ($gy <= 1600) ? 621 : 1600;
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100))
          + ((int)(($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
    $jy += 33 * ((int)($days / 12053));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    $jm = ($days < 186) ? 1 + (int)($days / 31) : 7 + (int)(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}
function jdate($format, $ts = null) {
    $ts = $ts ?: time();
    [$jy, $jm, $jd] = g2j((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    $out = str_replace(
        ['Y', 'm', 'd', 'n', 'j', 'M'],
        [sprintf('%04d', $jy), sprintf('%02d', $jm), sprintf('%02d', $jd), $jm, $jd, $months[$jm]],
        $format
    );
    $out = str_replace(['H', 'i'], [date('H', $ts), date('i', $ts)], $out);
    return fa_num($out);
}
function jdate_human($datetime) {
    if (!$datetime) return '—';
    return jdate('Y/m/d H:i', strtotime($datetime));
}

/* ---------- زمان ---------- */
function now() { return date('Y-m-d H:i:s'); }

/* ---------- فلش پیام ---------- */
function flash_set($type, $msg) { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function flash_get() {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- ریدایرکت ---------- */
function redirect($path) { header('Location: ' . $path); exit; }

/* ---------- CSRF ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify() {
    $t = $_POST['_csrf'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        exit('نشست شما منقضی شده است. لطفاً صفحه را رفرش کنید.');
    }
}

/* ---------- تنظیمات ---------- */
function setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (q("SELECT skey, svalue FROM settings") as $r) $cache[$r['skey']] = $r['svalue'];
        } catch (Throwable $ex) { /* قبل از نصب */ }
    }
    return $cache[$key] ?? $default;
}

/* ---------- درخت دسته‌بندی ---------- */
function category_tree($activeOnly = true) {
    static $tree = null, $treeActive = null;
    $key = $activeOnly ? 'a' : 'all';
    if ($key === 'a' && $treeActive !== null) return $treeActive;
    if ($key === 'all' && $tree !== null) return $tree;
    $rows = q("SELECT * FROM categories ORDER BY sort_order, name");
    $byParent = [];
    foreach ($rows as $r) { $byParent[$r['parent_id'] ?: 0][] = $r; }
    $build = function ($parent) use (&$build, &$byParent, $activeOnly) {
        $out = [];
        foreach ($byParent[$parent] ?? [] as $r) {
            if ($activeOnly && !$r['is_active']) continue;
            $r['children'] = $build((int)$r['id']);
            $out[] = $r;
        }
        return $out;
    };
    if ($key === 'a') { $treeActive = $build(0); return $treeActive; }
    $tree = $build(0); return $tree;
}
function category_children_flat($id, $activeOnly = true) {
    // خود دسته + تمام زیرشاخه‌ها (برای فیلتر محصول)
    $ids = [(int)$id];
    $rows = q("SELECT id, parent_id FROM categories" . ($activeOnly ? " WHERE is_active = 1" : ""));
    $changed = true;
    while ($changed) {
        $changed = false;
        foreach ($rows as $r) {
            $p = (int)($r['parent_id'] ?: 0);
            if (in_array($p, $ids) && !in_array((int)$r['id'], $ids)) { $ids[] = (int)$r['id']; $changed = true; }
        }
    }
    return $ids;
}
function category_path($id) {
    $path = [];
    $guard = 0;
    while ($id && $guard++ < 10) {
        $c = q1("SELECT * FROM categories WHERE id = ?", [$id]);
        if (!$c) break;
        array_unshift($path, $c);
        $id = (int)($c['parent_id'] ?: 0);
    }
    return $path;
}

/* ---------- برچسب وضعیت‌ها ---------- */
function order_statuses() {
    return [
        'pending'    => 'در انتظار تایید',
        'confirmed'  => 'تایید شده',
        'processing' => 'در حال آماده‌سازی',
        'shipped'    => 'ارسال شده',
        'delivered'  => 'تحویل شده',
        'cancelled'  => 'لغو شده',
    ];
}
function order_status_label($s) { return order_statuses()[$s] ?? $s; }
function order_status_class($s) {
    return ['pending'=>'w','confirmed'=>'b','processing'=>'p','shipped'=>'o','delivered'=>'g','cancelled'=>'r'][$s] ?? 'w';
}
function invoice_statuses() {
    return ['unpaid' => 'پرداخت نشده', 'paid' => 'پرداخت شده', 'cancelled' => 'ابطال شده'];
}
function invoice_status_label($s) { return invoice_statuses()[$s] ?? $s; }
function invoice_status_class($s) { return ['unpaid'=>'o','paid'=>'g','cancelled'=>'r'][$s] ?? 'w'; }

/* ---------- کاربر ---------- */
function current_user() { return $_SESSION['user'] ?? null; }
function is_admin() { return (current_user()['role'] ?? '') === 'admin'; }
function is_seller() { return (current_user()['role'] ?? '') === 'seller'; }
function is_staff() { return is_admin() || is_seller(); }
function unread_messages_count($userId) {
    return (int)qv("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0", [$userId]);
}

/* ---------- تصویر محصول ---------- */
function product_image($path) {
    if ($path && file_exists(__DIR__ . '/../' . ltrim($path, '/'))) return e($path);
    return 'assets/img/no-image.svg';
}

/* ---------- کد یکتا ---------- */
function pad_code($prefix, $id) { return $prefix . str_pad((string)$id, 6, '0', STR_PAD_LEFT); }

/* ---------- ستاره امتیاز ---------- */
function stars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= icon('star', $i <= round($rating) ? 'st-on' : 'st-off');
    return $out;
}

/* ---------- فونت سایت (قابل تغییر از پنل) ---------- */
function custom_font_info() {
    $file = basename((string)setting('custom_font_file', ''));
    if (!$file || !preg_match('/^custom_[a-f0-9]{16,32}\.(woff2?|ttf|otf)$/i', $file)) return null;
    $path = __DIR__ . '/../uploads/fonts/' . $file;
    if (!is_file($path)) return null;
    return [
        'file' => $file,
        'path' => $path,
        'name' => setting('custom_font_name', 'فونت دلخواه'),
        'ext' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
    ];
}
function available_fonts() {
    $fonts = [
        'vazirmatn' => ['label' => 'وزیرمتن (پیش‌فرض)',   'family' => 'Vazirmatn'],
        'shabnam'   => ['label' => 'شبنم',                'family' => 'Shabnam'],
        'sahel'     => ['label' => 'ساحل',                'family' => 'Sahel'],
        'samim'     => ['label' => 'صمیم',                'family' => 'Samim'],
        'parastoo'  => ['label' => 'پرستو',               'family' => 'Parastoo'],
        'gandom'    => ['label' => 'گندم',                'family' => 'Gandom'],
        'tanha'     => ['label' => 'طنها',                'family' => 'Tanha'],
        'tahoma'    => ['label' => 'تاهوما (سیستمی)',     'family' => 'Tahoma'],
    ];
    if ($custom = custom_font_info()) {
        $fonts['custom'] = ['label' => $custom['name'] . ' (فونت دلخواه)', 'family' => 'ParcheCustom'];
    }
    return $fonts;
}
function font_head_tags($assetBase = 'assets/', $rootBase = '') {
    $fonts = available_fonts();
    $key = setting('font_family', 'vazirmatn');
    $f = $fonts[$key] ?? $fonts['vazirmatn'];
    $scaleKey = setting('font_scale', '1');
    $scale = ['0.9' => '0.92', '1' => '1', '1.1' => '1.08'][$scaleKey] ?? '1';
    $out = '<link rel="stylesheet" href="' . e($assetBase . 'css/fonts.css') . '">' . "\n";
    if ($key === 'custom' && ($custom = custom_font_info())) {
        $format = $custom['ext'] === 'woff2' ? 'woff2' : ($custom['ext'] === 'woff' ? 'woff' : ($custom['ext'] === 'otf' ? 'opentype' : 'truetype'));
        $url = $rootBase . 'uploads/fonts/' . rawurlencode($custom['file']);
        $out .= '<style>@font-face{font-family:\'ParcheCustom\';src:url(\'' . e($url) . '\') format(\'' . $format . '\');font-style:normal;font-weight:100 900;font-display:swap;}</style>' . "\n";
    }
    return $out . '<style>:root{--app-font:\'' . e($f['family']) . '\',Vazirmatn,Tahoma,\'Segoe UI\',sans-serif;--font-scale:' . $scale . ';}</style>';
}
