<?php
/**
 * لایه دیتابیس — PDO با دو درایور: mysql (تولید/IIS) و sqlite (دمو/توسعه)
 */
if (!defined('PARCHE')) exit;

function db_config() {
    if (!file_exists(__DIR__ . '/config.php')) return null;
    return require __DIR__ . '/config.php';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $c = db_config();
    if (!$c) return $pdo; // قبل از نصب
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $path = $c['sqlite_path'] ?: (__DIR__ . '/../data/parche.sqlite');
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        try { $pdo->exec("PRAGMA journal_mode = DELETE"); } catch (Throwable $e) {}
        try { $pdo->exec("PRAGMA foreign_keys = ON"); } catch (Throwable $e) {}
    } else {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $c['host'], $c['port'] ?: '3306', $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
        try { $pdo->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'"); } catch (Throwable $e) {}
    }
    return $pdo;
}

/* اجرای کوئری */
function q($sql, $params = []) {
    $st = db()->prepare($sql);
    $st->execute(array_values($params));
    return $st;
}
/* یک ردیف */
function q1($sql, $params = []) {
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}
/* یک مقدار */
function qv($sql, $params = []) {
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}
/* آخرین id */
function last_id() { return (int)db()->lastInsertId(); }

/* پلاسهولدر IN (...) */
function in_ph($arr) { return implode(',', array_fill(0, count($arr), '?')); }

/* تراکنش امن (بدون تو در تو) */
function tx_start() { if (!db()->inTransaction()) db()->beginTransaction(); }
function tx_commit() { if (db()->inTransaction()) db()->commit(); }
function tx_rollback() { if (db()->inTransaction()) db()->rollBack(); }

/* صفحه‌بندی: LIMIT/OFFSET */
function page_params($perPage = 12) {
    $p = max(1, (int)($_GET['p'] ?? 1));
    return ['page' => $p, 'offset' => ($p - 1) * $perPage, 'limit' => $perPage];
}
