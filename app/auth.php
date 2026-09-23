<?php
/**
 * احراز هویت و مدیریت کاربران
 */
if (!defined('PARCHE')) exit;

function auth_attempt($login, $password) {
    $login = trim($login);
    $u = q1("SELECT * FROM users WHERE (email = ? OR phone = ?) LIMIT 1", [$login, en_num($login)]);
    if (!$u || !password_verify($password, $u['password'])) return null;
    if (!(int)$u['status']) return 'disabled';
    return $u;
}

function auth_login(array $u) {
    $_SESSION['user'] = [
        'id' => (int)$u['id'], 'name' => $u['name'], 'role' => $u['role'],
        'email' => $u['email'], 'phone' => $u['phone'], 'shop_name' => $u['shop_name'],
    ];
}

function auth_logout() { unset($_SESSION['user']); }

function require_login() {
    if (!current_user()) { flash_set('e', 'برای ادامه ابتدا وارد حساب خود شوید.'); redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '')); }
}
function require_admin() {
    if (!is_admin()) { http_response_code(403); exit('دسترسی فقط برای مدیر سیستم مجاز است.'); }
}

function auth_register($name, $phone, $email, $password) {
    $phone = en_num(trim($phone));
    if ($phone && q1("SELECT id FROM users WHERE phone = ?", [$phone])) return ['خطای شماره موبایل', 'این شماره موبایل قبلاً ثبت شده است.'];
    if ($email && q1("SELECT id FROM users WHERE email = ?", [$email])) return ['خطای ایمیل', 'این ایمیل قبلاً ثبت شده است.'];
    q("INSERT INTO users (name, phone, email, password, role, status, created_at) VALUES (?,?,?,?,'customer',1,?)",
        [$name, $phone, $email ?: null, password_hash($password, PASSWORD_DEFAULT), now()]);
    return true;
}

/* محدوده فروشنده: محصول/سفارش/فاکتور خودش */
function seller_scope_sql($col, &$params) {
    if (is_seller()) { $params[] = current_user()['id']; return " AND $col = ? "; }
    return '';
}
