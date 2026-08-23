<?php
require_once dirname(__DIR__) . '/_private/bootstrap.php';

nkuba_json_headers();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = nkuba_pdo();

    switch ($action) {
        case 'list':
            $rows = $pdo->query('SELECT * FROM guests ORDER BY id DESC')->fetchAll();
            $guests = array_map('nkuba_guest_row_to_array', $rows);
            echo json_encode(['success' => true, 'guests' => $guests]);
            break;

        case 'sync':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'POST required']);
                break;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $guests = $input['guests'] ?? [];
            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM guests');
            $ins = $pdo->prepare(
                'INSERT INTO guests (full_name, whatsapp, table_zone, seats, style_id, sent, device_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($guests as $g) {
                $ins->execute([
                    $g['fullName'] ?? '',
                    preg_replace('/\D/', '', $g['whatsapp'] ?? ''),
                    $g['tableZone'] ?? '',
                    (int)($g['seats'] ?? 1),
                    $g['styleId'] ?? 'mariage-civil',
                    !empty($g['sent']) ? 1 : 0,
                    isset($g['id']) ? (int)$g['id'] : null,
                    $g['createdAt'] ?? date('Y-m-d H:i:s'),
                ]);
            }
            if (!empty($input['config']) && is_array($input['config'])) {
                foreach ($input['config'] as $k => $v) {
                    nkuba_set_config($k, (string)$v);
                }
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'count' => count($guests)]);
            break;

        case 'export':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="invites-nkuba-kasongo.csv"');
            $rows = $pdo->query('SELECT * FROM guests ORDER BY full_name')->fetchAll();
            echo "\xEF\xBB\xBF";
            echo "Nom,WhatsApp,Table,Places,Envoye,Style\n";
            foreach ($rows as $g) {
                echo sprintf('"%s",%s,"%s",%d,%s,"%s"' . "\n",
                    str_replace('"', '""', $g['full_name']),
                    $g['whatsapp'],
                    str_replace('"', '""', $g['table_zone']),
                    (int)$g['seats'],
                    $g['sent'] ? 'Oui' : 'Non',
                    $g['style_id']
                );
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Action inconnue']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Service temporairement indisponible']);
}
