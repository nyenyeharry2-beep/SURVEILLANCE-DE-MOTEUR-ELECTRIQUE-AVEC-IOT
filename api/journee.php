<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $action = $body['action'] ?? $_GET['action'] ?? 'status';
    if ($action === 'open') {
        $_GET['route'] = 'journee/open';
    } elseif ($action === 'close') {
        $_GET['route'] = 'journee/close';
    } else {
        $_GET['route'] = 'journee';
    }
} else {
    $_GET['route'] = 'journee';
}

require __DIR__ . '/index.php';
