<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

if (!isset($_GET['route']) || $_GET['route'] === '') {
    $_GET['route'] = 'auth/login';
}
require __DIR__ . '/index.php';
