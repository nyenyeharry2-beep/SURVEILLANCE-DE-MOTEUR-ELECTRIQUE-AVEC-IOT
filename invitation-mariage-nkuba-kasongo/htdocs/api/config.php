<?php
require_once dirname(__DIR__) . '/_private/bootstrap.php';

nkuba_json_headers();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'config' => [
            'event_date' => nkuba_get_config('event_date', 'Vendredi, le 11 Septembre 2026'),
            'event_time' => nkuba_get_config('event_time', '11h00'),
            'event_venue' => nkuba_get_config('event_venue', 'Commune de Kipushi, Ville de KIPUSHI'),
            'whatsapp_message' => nkuba_get_config('whatsapp_message', ''),
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    foreach ($input as $k => $v) {
        nkuba_set_config($k, (string)$v);
    }
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Enregistrement impossible']);
}
