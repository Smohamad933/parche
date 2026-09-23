<?php
/**
 * نصب از خط فرمان (برای محیط توسعه/دمو):
 *   php install/cli-install.php [sqlite-path]
 */
define('PARCHE_SKIP_INSTALL_CHECK', true);
if (PHP_SAPI !== 'cli') exit("فقط از خط فرمان اجرا کنید.\n");
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../app/schema.php';

$path = $argv[1] ?? (__DIR__ . '/../data/parche.sqlite');
@mkdir(dirname($path), 0775, true);
@unlink($path);

$pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('PRAGMA foreign_keys = ON');
schema_install($pdo, 'sqlite');
seed_demo($pdo, 'sqlite');

/* مدیر: در دمو با داده seed ساخته می‌شود؛ اگر نبود اضافه کن */
if (!$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) {
    $pdo->prepare("INSERT INTO users (name, phone, email, password, role, status, created_at) VALUES (?,?,?,?, 'admin', 1, ?)")
        ->execute(['مدیر دمو', '09120000000', 'admin@parche.local', password_hash('admin123', PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
}

file_put_contents(__DIR__ . '/../app/config.php', "<?php\nreturn " . var_export([
    'driver' => 'sqlite',
    'host' => '', 'port' => '', 'name' => '', 'user' => '', 'pass' => '',
    'sqlite_path' => realpath($path),
], true) . ";\n");

echo "✔ نصب کامل شد (SQLite: $path)\n";
echo "  مدیر: admin@parche.local / admin123\n";
echo "  فروشنده: alborz@parche.local / seller123\n";
echo "  مشتری: customer@parche.local / 12345678\n";
