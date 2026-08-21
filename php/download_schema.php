<?php
/**
 * download_schema.php - Telecharge schema.sql directement
 * Ouvrir : http://surveillancemoteurharry.ct.ws/download_schema.php
 */
$file = __DIR__ . '/schema.sql';
if (!file_exists($file)) {
    http_response_code(404);
    echo 'Fichier schema.sql introuvable';
    exit;
}

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="schema.sql"');
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
