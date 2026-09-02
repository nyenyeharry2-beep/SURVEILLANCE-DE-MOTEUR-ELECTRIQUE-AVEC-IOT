<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

$type = $_GET['type'] ?? 'jour';
$_GET['route'] = $type === 'mois' ? 'rapports/mois' : 'rapports/jour';
require __DIR__ . '/index.php';
