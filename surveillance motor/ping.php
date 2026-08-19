<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
echo json_encode([
  'ok' => true,
  'service' => 'lumen',
  'mesure' => 'mesure.php',
], JSON_UNESCAPED_UNICODE);
