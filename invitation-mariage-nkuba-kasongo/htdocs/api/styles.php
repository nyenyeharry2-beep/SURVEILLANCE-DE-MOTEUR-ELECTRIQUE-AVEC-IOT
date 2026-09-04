<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$path = dirname(__DIR__) . '/assets/styles.json';
if (!file_exists($path)) {
    echo json_encode(['success' => false, 'error' => 'styles.json introuvable']);
    exit;
}
$data = json_decode(file_get_contents($path), true);
echo json_encode(['success' => true] + $data);
