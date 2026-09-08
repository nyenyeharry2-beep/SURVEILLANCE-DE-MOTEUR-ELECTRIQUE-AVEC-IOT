<?php
declare(strict_types=1);

/**
 * API publique — liste des communiqués (polling APK / suivi.php)
 */
define('SUPERGENIES_NO_HEADERS', true);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée', 405);
}

try {
    $communiques = fetchCommuniques(getPdo(), 30);
    jsonResponse([
        'success' => true,
        'communiques' => array_map(static function (array $c): array {
            return [
                'id' => (int) $c['id'],
                'titre' => $c['titre'],
                'contenu' => $c['contenu'],
                'created_at' => $c['created_at'],
            ];
        }, $communiques),
    ]);
} catch (Throwable $e) {
    jsonError('Service indisponible', 503);
}
