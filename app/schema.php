<?php
/**
 * تعریف اسکیمای دیتابیس + داده اولیه دمو
 * سازگار با MySQL (تولید روی IIS) و SQLite (دمو محلی)
 */
if (!defined('PARCHE')) exit;

/* ---------- DDL (به زبان MySQL؛ برای SQLite خودکار تبدیل می‌شود) ---------- */
function schema_tables() {
    return [
"CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','seller','customer') NOT NULL DEFAULT 'customer',
  shop_name VARCHAR(190) DEFAULT NULL,
  status TINYINT NOT NULL DEFAULT 1,
  created_at VARCHAR(19) NOT NULL
)",
"CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  parent_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  description TEXT DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  balance INT NOT NULL DEFAULT 0,
  is_active TINYINT NOT NULL DEFAULT 1,
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
)",
"CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  category_id INT UNSIGNED DEFAULT NULL,
  seller_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(190) NOT NULL,
  code VARCHAR(40) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  price DECIMAL(15,0) NOT NULL DEFAULT 0,
  old_price DECIMAL(15,0) DEFAULT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'متر',
  stock DECIMAL(10,1) NOT NULL DEFAULT 0,
  low_stock INT NOT NULL DEFAULT 5,
  min_order DECIMAL(6,1) NOT NULL DEFAULT 0.5,
  width_cm INT DEFAULT NULL,
  material VARCHAR(190) DEFAULT NULL,
  color VARCHAR(120) DEFAULT NULL,
  pattern VARCHAR(120) DEFAULT NULL,
  origin VARCHAR(120) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  is_featured TINYINT NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
)",
"CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS inventory_logs (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  change_qty DECIMAL(10,1) NOT NULL DEFAULT 0,
  type ENUM('in','out','adjust','order','return') NOT NULL DEFAULT 'in',
  note VARCHAR(255) DEFAULT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  stock_after DECIMAL(10,1) NOT NULL DEFAULT 0,
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(30) DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  address TEXT DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  postal_code VARCHAR(20) DEFAULT NULL,
  note TEXT DEFAULT NULL,
  subtotal DECIMAL(15,0) NOT NULL DEFAULT 0,
  discount DECIMAL(15,0) NOT NULL DEFAULT 0,
  shipping_cost DECIMAL(15,0) NOT NULL DEFAULT 0,
  total DECIMAL(15,0) NOT NULL DEFAULT 0,
  coupon_code VARCHAR(40) DEFAULT NULL,
  status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  created_at VARCHAR(19) NOT NULL,
  updated_at VARCHAR(19) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)",
"CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(190) NOT NULL,
  seller_id INT UNSIGNED DEFAULT NULL,
  qty DECIMAL(10,1) NOT NULL DEFAULT 1,
  unit_price DECIMAL(15,0) NOT NULL DEFAULT 0,
  total DECIMAL(15,0) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS order_history (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  order_id INT UNSIGNED NOT NULL,
  status VARCHAR(30) NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  by_user_id INT UNSIGNED DEFAULT NULL,
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(30) DEFAULT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  seller_id INT UNSIGNED DEFAULT NULL,
  customer_name VARCHAR(120) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  subtotal DECIMAL(15,0) NOT NULL DEFAULT 0,
  discount DECIMAL(15,0) NOT NULL DEFAULT 0,
  tax DECIMAL(15,0) NOT NULL DEFAULT 0,
  total DECIMAL(15,0) NOT NULL DEFAULT 0,
  status ENUM('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  note VARCHAR(255) DEFAULT NULL,
  issued_at VARCHAR(19) NOT NULL,
  paid_at VARCHAR(19) DEFAULT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
)",
"CREATE TABLE IF NOT EXISTS coupons (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(40) NOT NULL UNIQUE,
  type ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  value DECIMAL(15,0) NOT NULL DEFAULT 0,
  min_amount DECIMAL(15,0) NOT NULL DEFAULT 0,
  max_uses INT NOT NULL DEFAULT 0,
  used_count INT NOT NULL DEFAULT 0,
  expires_at VARCHAR(19) DEFAULT NULL,
  is_active TINYINT NOT NULL DEFAULT 1
)",
"CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  rating TINYINT NOT NULL DEFAULT 5,
  comment TEXT DEFAULT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  sender_id INT UNSIGNED DEFAULT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  subject VARCHAR(190) NOT NULL,
  body TEXT DEFAULT NULL,
  is_read TINYINT NOT NULL DEFAULT 0,
  created_at VARCHAR(19) NOT NULL,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(80) NOT NULL PRIMARY KEY,
  svalue TEXT DEFAULT NULL
)",
    ];
}

function schema_indexes() {
    return [
        "CREATE INDEX IF NOT EXISTS idx_products_cat ON products (category_id)",
        "CREATE INDEX IF NOT EXISTS idx_products_status ON products (status)",
        "CREATE INDEX IF NOT EXISTS idx_orders_user ON orders (user_id)",
        "CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id)",
        "CREATE INDEX IF NOT EXISTS idx_items_seller ON order_items (seller_id)",
        "CREATE INDEX IF NOT EXISTS idx_inv_product ON inventory_logs (product_id)",
        "CREATE INDEX IF NOT EXISTS idx_msg_receiver ON messages (receiver_id)",
        "CREATE INDEX IF NOT EXISTS idx_reviews_product ON reviews (product_id)",
    ];
}

/* تبدیل DDL مای‌اس‌کیو‌ال به SQLite */
function sqlite_adapt_ddl($sql) {
    $sql = preg_replace('/\bENUM\s*\([^)]*\)/i', 'TEXT', $sql);
    $sql = preg_replace('/(\w+)\s+INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT/i', '$1 INTEGER PRIMARY KEY', $sql);
    $sql = preg_replace('/\bINT UNSIGNED NOT NULL AUTO_INCREMENT/i', 'INTEGER NOT NULL', $sql);
    $sql = preg_replace('/\bINT UNSIGNED\b/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bTINYINT\b/i', 'INTEGER', $sql);
    $sql = preg_replace('/\bVARCHAR\(\d+\)/i', 'TEXT', $sql);
    $sql = preg_replace('/\bDECIMAL\(\d+,\s*\d+\)/i', 'NUMERIC', $sql);
    $sql = preg_replace('/\)\s*ENGINE=.*$/is', ')', $sql);
    return $sql;
}

/* ---------- نصب جداول ---------- */
function schema_install(PDO $pdo, $driver) {
    foreach (schema_tables() as $sql) {
        $pdo->exec($driver === 'sqlite' ? sqlite_adapt_ddl($sql) : $sql . " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    foreach (schema_indexes() as $sql) {
        try { $pdo->exec($sql); } catch (Throwable $e) { /* MySQL از IF NOT EXISTS ایندکس پشتیبانی نمی‌کند در نسخه‌های قدیمی */ }
    }
}

/* ---------- داده اولیه دمو ---------- */
function seed_demo(PDO $pdo, $driver) {
    $now = date('Y-m-d H:i:s');

    /* کاربران */
    $users = [
        ['مدیر پارچه‌سرا', '09120000001', 'admin@parche.local', 'admin123', 'admin', null],
        ['فروشندگی البرز', '09120000002', 'alborz@parche.local', 'seller123', 'seller', 'پارچه‌فروشی البرز'],
        ['نساجی پارس', '09120000003', 'pars@parche.local', 'seller123', 'seller', 'نساجی پارس'],
        ['مشتری نمونه', '09120000004', 'customer@parche.local', '12345678', 'customer', null],
    ];
    $st = $pdo->prepare("INSERT INTO users (name, phone, email, password, role, shop_name, status, created_at) VALUES (?,?,?,?,?,?,1,?)");
    foreach ($users as $u) $st->execute([$u[0], $u[1], $u[2], password_hash($u[3], PASSWORD_DEFAULT), $u[4], $u[5], $now]);

    /* دسته‌بندی درختی */
    $cats = [
        // id, parent, name, sort, balance
        [1, null, 'پارچه‌های طبیعی', 1, 10],
        [2, null, 'پارچه‌های شیمیایی و براق', 2, 8],
        [3, null, 'پارچه‌های مزون و مجلسی', 3, 9],
        [4, null, 'پارچه‌های کاربردی', 4, 6],
        [5, 1, 'پارچه نخی', 1, 7],
        [6, 1, 'کتان و لینن', 2, 5],
        [7, 1, 'پشم و نیم‌پشم', 3, 4],
        [8, 1, 'ابریشم طبیعی', 4, 6],
        [9, 2, 'ساتن', 1, 6],
        [10, 2, 'حریر', 2, 5],
        [11, 2, 'ترگال', 3, 8],
        [12, 2, 'دیسکو', 4, 4],
        [13, 2, 'پادرا', 5, 5],
        [14, 3, 'تور و گلدوزی', 1, 5],
        [15, 3, 'کرپ مزون', 2, 7],
        [16, 3, 'مخمل مجلسی', 3, 4],
        [17, 4, 'جین و دنیم', 1, 6],
        [18, 4, 'فوتر و گرم‌باف', 2, 4],
        [19, 4, 'مبلی و پرده', 3, 3],
    ];
    $st = $pdo->prepare("INSERT INTO categories (id, parent_id, name, description, sort_order, balance, is_active, created_at) VALUES (?,?,?,?,?,?,1,?)");
    foreach ($cats as $c) {
        $desc = 'مجموعه‌ای از بهترین ' . $c[2] . ' با کیفیت تضمینی و قیمت مناسب برای دوخت و طراحی.';
        $st->execute([$c[0], $c[1], $c[2], $desc, $c[3], $c[4], $now]);
    }

    /* محصولات */
    // [cat, seller, name, price, old, stock, featured, width, material, color, pattern, origin, desc]
    $products = [
        [5, 2, 'چیت گلدوزی طرح گل رز', 185000, 210000, 120, 1, 150, '۱۰۰٪ نخ پنبه', 'کرم / گل رز', 'طرحدار گل‌دار', 'ایران - یزد', 'چیت نخی درجه یک با طرح گل رز، مناسب مانتو، پیراهن و لباس روزمره. رنگ ثابت و لطیف روی پوست.'],
        [16, null, 'کالک نخی ساده لباس‌دوزی', 155000, null, 340, 1, 145, '۱۰۰٪ پنبه', 'سفید صادراتی', 'ساده', 'ایران - تهران', 'کالک نخی سفید صادراتی مناسب زیرکار، آستر، روتختی و رنگ‌سازی دستی.'],
        [6, 2, 'کتان لینن خالص تابستانی', 420000, 465000, 85, 1, 140, '۱۰۰٪ لینن', 'طوسی روشن', 'ساده بافت', 'چین', 'کتان خالص با ته‌مایه لطیف، عالی برای لباس تابستانی، شلوار و ست‌های کژوال. تنفس‌پذیر و سبک.'],
        [9, 3, 'ساتن ابریشمی براق درجه یک', 310000, null, 95, 1, 150, 'پلی‌استر ساتن', 'زرشکی', 'براق ساده', 'کره جنوبی', 'ساتن براق با افتادگی عالی، مناسب لباس مجلسی، دامن شلواری و شال.'],
        [10, 3, 'حریر شکوفه‌دار بنفش', 260000, 290000, 110, 0, 150, 'حریر پلی‌استر', 'بنفش یاسی', 'شکوفه‌دار', 'ایران', 'حریر نازک با طرح شکوفه، مناسب بلوز، روسری و لایه‌های مزون.'],
        [11, 2, 'ترگال پرچین صادراتی', 245000, null, 260, 1, 150, 'پلی‌استر ویسکوز', 'مشکی', 'پرچین', 'ایران - کاشان', 'ترگال کدر و نرم با کیفیت صادراتی؛ گزینه اول مانتو و شلوار اداری. چین‌های زیبا و ثابت.'],
        [12, 3, 'دیسکو درجه یک براق', 395000, 430000, 60, 0, 145, 'پلی‌استر کش', 'نقره‌ای', 'براق آینه‌ای', 'ترکیه', 'پارچه دیسکو با درخشش بالا برای لباس مجلسی و رقص؛ کشسانی مناسب و راحت.'],
        [13, 2, 'پادرای کدر مزون', 350000, null, 88, 1, 150, 'پلی‌استر فلامنت', 'کرم شکلاتی', 'کدر ساده', 'ایران', 'پادرا با دید کدر و حالت‌پذیری عالی، مناسب مانتوی مجلسی و ست دوتکه.'],
        [14, 3, 'تور فرانسوی گلدوزی ظریف', 480000, 540000, 45, 1, 145, 'تور نخ گلدوزی', 'شیری', 'گلدوزی‌شده', 'فرانسه', 'تور گلدوزی ظریف با نقش‌های دقیق، برای دامن مجلسی، آستین و لایه رویایی لباس عروس.'],
        [15, 2, 'کرپ مازراتی مزون', 425000, null, 74, 1, 150, 'کرپ ویسکوز', 'دودی', 'ساده نرم', 'ایران - تهران', 'کرپ مازراتی با کشش عرضی و افت عالی؛ انتخاب اول مانتو و پالتو مزون.'],
        [16, 3, 'مخمل سلطنتی پشمی', 520000, 580000, 38, 0, 140, 'مخمل پنبه‌پشم', 'سبز زمردی', 'ساده مخمل', 'ایتالیا', 'مخمل سنگ‌وزن با لمس مخملی عمیق؛ مناسب پالتو، ژاکت و دکور مجلسی.'],
        [17, 2, 'جین دنیم ۱۲ اونس', 290000, null, 210, 1, 150, '۱۰۰٪ دنیم', 'آبی سیر', 'مشت‌بافته', 'ایران - تهران', 'دنیم سنگین ۱۲ اونس برای شلوار جین، ژاکت و دامن؛ دوخت مقاوم و رنگ ثابت.'],
        [18, 2, 'فوتر پرز زمستانی', 380000, 410000, 130, 0, 155, 'نخ پنبه + پرز', 'طوسی melt', 'ساده با پرز', 'ایران', 'فوتر پرز گرم و لطیف برای هودی، شلوار ورزشی و لباس بچگانه زمستانی.'],
        [19, 2, 'پارچه مبل مخمل‌کوب', 340000, null, 175, 0, 280, 'مخمل مبلی', 'سرمه‌ای', 'طرح بافته', 'ایران - کاشان', 'پارچه مبلی مخمل‌کوب با عرض ۲۸۰ و مقاومت سایش بالا؛ مناسب مبل، کوسن و پرده.'],
        [8, 3, 'ابریشم طبیعی خراسان', 890000, 950000, 22, 1, 140, '۱۰۰٪ ابریشم طبیعی', 'عسلی', 'براق طبیعی', 'ایران - خراسان', 'ابریشم اصل با درخشش طبیعی؛ محدود و ویژه لباس فاخر و شال دست‌دوز.'],
        [7, 2, 'پشم‌وپنبه اسکاتلندی', 465000, null, 56, 0, 150, 'پشم و پنبه', 'قرمز چهارخانه', 'اسکاتلندی', 'انگلستان', 'پارچه چهارخانه کلاسیک برای دامن اسکاتلندی، پالتو و اکسسوری پاییزه.'],
    ];
    $stP = $pdo->prepare("INSERT INTO products (category_id, seller_id, name, code, description, price, old_price, unit, stock, low_stock, min_order, width_cm, material, color, pattern, origin, status, is_featured, views, created_at)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'active', ?, ?, ?)");
    $stI = $pdo->prepare("INSERT INTO product_images (product_id, path, sort_order) VALUES (?,?,0)");
    $stL = $pdo->prepare("INSERT INTO inventory_logs (product_id, user_id, change_qty, type, note, stock_after, created_at) VALUES (?,?,?,?,?,?,?)");
    $pid = 0;
    foreach ($products as $i => $p) {
        $pid++;
        $code = 'PRC-' . str_pad((string)$pid, 4, '0', STR_PAD_LEFT);
        $stP->execute([$p[0], $p[1], $p[2], $code, $p[12], $p[3], $p[4], 'متر', $p[5], 5, 0.5, $p[7], $p[8], $p[9], $p[10], $p[11], $p[6], rand(30, 480), $now]);
        $stI->execute([$pid, 'uploads/products/p' . str_pad((string)$pid, 2, '0', STR_PAD_LEFT) . '.jpg']);
        $stL->execute([$pid, 2, $p[5], 'in', 'موجودی اولیه انبار', $p[5], $now]);
    }

    /* کدهای تخفیف */
    $stC = $pdo->prepare("INSERT INTO coupons (code, type, value, min_amount, max_uses, used_count, expires_at, is_active) VALUES (?,?,?,?,?,?,?,1)");
    $stC->execute(['WELCOME10', 'percent', 10, 200000, 500, 1, null]);
    $stC->execute(['NOV50K', 'fixed', 50000, 500000, 100, 0, null]);

    /* تنظیمات */
    $settings = [
        'site_name' => 'پارچه‌سرا',
        'site_tagline' => 'فروشگاه جامع انواع پارچه — طبیعی، شیمیایی و مزون',
        'hero_title' => 'هر پارچه‌ای که تصور می‌کنید، اینجاست',
        'hero_subtitle' => 'بیش از ۱۹ دسته پارچه با قیمت متر و موجودی لحظه‌ای انبار؛ سفارش شما همان روز ثبت و ارسال می‌شود.',
        'phone' => '۰۲۱-۹۱۰۰۸۸۰۰',
        'mobile' => '۰۹۱۲-۰۰۰-۰۰۰۱',
        'email' => 'info@parche.local',
        'address' => 'تهران، بازار بزرگ، راسته پارچه‌فروش‌ها، پلاک ۱۲',
        'instagram' => 'parchesara',
        'telegram' => 'parchesara',
        'whatsapp' => '09120000001',
        'shipping_flat' => '45000',
        'free_shipping_min' => '3000000',
        'about_text' => "پارچه‌سرا از سال ۱۳۹۰ در قلب بازار پارچه تهران فعالیت می‌کند. ما عرضه‌ی مستقیم انواع پارچه‌های طبیعی، شیمیایی و مزون را با قیمت درب کارخانه انجام می‌دهیم.\nتمام پارچه‌ها قبل از ارسال کنترل کیفیت می‌شوند و امکان مرجوعی تا ۷ روز برای مشتریان گرامی فراهم است.",
        'footer_note' => 'کلیه حقوق مادی و معنوی این وب‌سایت متعلق به پارچه‌سرا است.',
        'currency' => 'تومان',
    ];
    $stS = $pdo->prepare("INSERT INTO settings (skey, svalue) VALUES (?,?)");
    foreach ($settings as $k => $v) $stS->execute([$k, $v]);

    /* نظرات نمونه */
    $reviews = [
        [2, 4, 'مریم رضایی', 5, 'کیفیت کتان واقعاً عالیه، برای مانتو تابستونی گرفتم و خیلی خنکه.', 'approved'],
        [3, 4, 'سارا محمدی', 4, 'ساتن براق و قشنگیه، فقط یه کم ریزه‌کاری دوخت می‌خواد.', 'approved'],
        [9, 4, 'نگار کریمی', 5, 'کرپ مازراتی دقیقاً همون چیزی بود که تو توضیحات نوشته؛ افت عالی.', 'approved'],
        [6, 4, 'الهام ت.', 5, 'ترگال پرچینش فوق‌العاده‌ست، برای مانتو اداری بهترین انتخابه.', 'pending'],
    ];
    $stR = $pdo->prepare("INSERT INTO reviews (product_id, user_id, name, rating, comment, status, created_at) VALUES (?,?,?,?,?,?,?)");
    foreach ($reviews as $r) $stR->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $now]);

    /* سفارش‌های نمونه */
    $demoOrders = [
        // [status, coupon, items: [[pid, qty]]]
        ['delivered', null, [[1, 3], [3, 2.5]]],
        ['confirmed', 'WELCOME10', [[6, 4], [12, 2]]],
    ];
    $stO  = $pdo->prepare("INSERT INTO orders (code, user_id, customer_name, phone, address, city, postal_code, note, subtotal, discount, shipping_cost, total, coupon_code, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stOI = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, seller_id, qty, unit_price, total) VALUES (?,?,?,?,?,?,?)");
    $stOH = $pdo->prepare("INSERT INTO order_history (order_id, status, note, by_user_id, created_at) VALUES (?,?,?,?,?)");
    $stIN = $pdo->prepare("INSERT INTO invoices (code, order_id, seller_id, customer_name, phone, subtotal, discount, tax, total, status, note, issued_at, paid_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($demoOrders as $n => $d) {
        $sub = 0;
        $items = [];
        foreach ($d[2] as $it) {
            $prod = $pdo->query("SELECT * FROM products WHERE id = " . (int)$it[0])->fetch();
            $line = $prod['price'] * $it[1];
            $sub += $line;
            $items[] = [$prod, $it[1], $line];
        }
        $disc = 0;
        if ($d[1]) $disc = (int)round($sub * 0.10);
        $ship = $sub >= 3000000 ? 0 : 45000;
        $total = $sub - $disc + $ship;
        $created = date('Y-m-d H:i:s', strtotime(($n === 0 ? '-6 days' : '-1 day')));
        $stO->execute([null, 4, 'مشتری نمونه', '09120000004', 'تهران، خیابان ولیعصر، کوچه یاس، پلاک ۸', 'تهران', '1234567890', 'لطفاً برای ارسال تماس بگیرید.', $sub, $disc, $ship, $total, $d[1], $d[0], $created, $created]);
        $oid = (int)$pdo->lastInsertId();
        $pdo->exec("UPDATE orders SET code = 'ORD-" . str_pad((string)$oid, 6, '0', STR_PAD_LEFT) . "' WHERE id = " . $oid);
        foreach ($items as $it) $stOI->execute([$oid, $it[0]['id'], $it[0]['name'], $it[0]['seller_id'], $it[1], $it[0]['price'], $it[2]]);
        $stOH->execute([$oid, 'pending', 'سفارش ثبت شد', 4, $created]);
        $stOH->execute([$oid, $d[0], 'به‌روزرسانی وضعیت توسط مدیر', 1, $created]);
        if ($d[0] === 'delivered') $paidAt = $created; else $paidAt = null;
        $stIN->execute([null, $oid, 2, 'مشتری نمونه', '09120000004', $sub, $disc, 0, $total - $ship, $d[0] === 'delivered' ? 'paid' : 'unpaid', 'فاکتور فروش پارچه', $created, $paidAt]);
        $iid = (int)$pdo->lastInsertId();
        $pdo->exec("UPDATE invoices SET code = 'INV-" . str_pad((string)$iid, 6, '0', STR_PAD_LEFT) . "' WHERE id = " . $iid);
    }

    /* پیام‌های نمونه */
    $stM = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body, is_read, created_at) VALUES (?,?,?,?,?,?)");
    $stM->execute([1, 4, 'به پارچه‌سرا خوش آمدید', "سلام!\nحساب شما در پارچه‌سرا ایجاد شد. با اولین خرید می‌توانید از کد تخفیف WELCOME10 استفاده کنید.\nهر سوالی داشتید همین‌جا برای ما بنویسید.", 1, $now]);
    $stM->execute([4, 1, 'سوال درباره ارسال', "سلام، سفارشم کی ارسال میشه؟ برای ارسال به شهرستان چند روز طول می‌کشه؟", 0, $now]);
}
