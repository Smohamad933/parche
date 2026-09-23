<?php
require_once __DIR__ . '/app/init.php';
auth_logout();
flash_set('s', 'از حساب خود خارج شدید.');
redirect('index.php');
