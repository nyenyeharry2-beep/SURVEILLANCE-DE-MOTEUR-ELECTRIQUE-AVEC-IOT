<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

// Test connexion : GET http://supergenies2026.site.je/api/admin/ping.php
jsonResponse([
    'success' => true,
    'message' => 'API Admin accessible',
    'time' => date('c'),
]);
