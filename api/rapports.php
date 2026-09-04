<?php

declare(strict_types=1);

if (!defined('NOUVELLE_EVE_API')) {
    define('NOUVELLE_EVE_API', true);
}

$type = $_GET['type'] ?? 'jour';
if ($type === 'mois') {
    $_GET['route'] = 'rapports/mois';
} elseif ($type === 'jours') {
    $_GET['route'] = 'rapports/jours';
} else {
    $_GET['route'] = 'rapports/jour';
}
require __DIR__ . '/index.php';
