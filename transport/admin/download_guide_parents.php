<?php
/**
 * Téléchargement affiche guide parents (PNG) — logo + QR + étapes
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/guide_parents_image.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (!extension_loaded('gd')) {
    http_response_code(500);
    exit('Extension GD requise.');
}

$preview = isset($_GET['preview']);
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (!$preview) {
    header('Content-Disposition: attachment; filename="guide-inscription-transport-super-genies.png"');
}

if (!generateGuideParentsPng('php://output')) {
    http_response_code(500);
    exit('Impossible de generer l\'affiche.');
}

logActivity('download_guide_parents', 'guide', null, 'Telechargement guide parents');
exit;
