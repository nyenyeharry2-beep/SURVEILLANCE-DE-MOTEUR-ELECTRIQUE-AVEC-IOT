<?php
/**
 * API PHP — Invitations Moïse & Sarah
 * Synchronisation invités et export CSV
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$dbFile = $dataDir . '/guests.json';

function loadGuests(string $file): array {
    if (!file_exists($file)) return [];
    $raw = file_get_contents($file);
    return json_decode($raw, true) ?: [];
}

function saveGuests(string $file, array $guests): void {
    file_put_contents($file, json_encode($guests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'guests' => loadGuests($dbFile)]);
        break;

    case 'sync':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $guests = $input['guests'] ?? [];
        saveGuests($dbFile, $guests);
        echo json_encode(['success' => true, 'count' => count($guests)]);
        break;

    case 'export':
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="invites.csv"');
        $guests = loadGuests($dbFile);
        echo "Nom,WhatsApp,Table,Places,Envoye\n";
        foreach ($guests as $g) {
            echo sprintf('"%s",%s,"%s",%d,%s' . "\n",
                str_replace('"', '""', $g['fullName'] ?? ''),
                $g['whatsapp'] ?? '',
                str_replace('"', '""', $g['tableZone'] ?? ''),
                $g['seats'] ?? 1,
                !empty($g['sent']) ? 'Oui' : 'Non'
            );
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
