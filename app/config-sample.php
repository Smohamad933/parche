<?php
/**
 * نمونه فایل پیکربندی
 * این فایل را به app/config.php کپی کنید و مقادیر را مطابق سرور خود تنظیم کنید.
 * (نصب‌کننده وب این فایل را به‌صورت خودکار می‌سازد)
 */
return [
    // درایور دیتابیس: 'mysql' برای IIS/MySQL  |  'sqlite' فقط برای تست محلی
    'driver' => 'mysql',

    // تنظیمات MySQL (روی IIS از این مقادیر استفاده می‌شود)
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'parche',
    'user' => 'parche',
    'pass' => 'CHANGE_ME',

    // مسیر فایل SQLite (فقط برای driver=sqlite)
    'sqlite_path' => __DIR__ . '/../data/parche.sqlite',
];
