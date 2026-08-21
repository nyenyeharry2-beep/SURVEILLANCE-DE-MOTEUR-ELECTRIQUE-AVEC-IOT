<?php
/**
 * get_command.php - Retourne la derniere commande en attente pour l'ESP32
 * Reponse: ON, OFF ou NONE
 */
require_once __DIR__ . '/config.php';

if (!checkApiKey()) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'NONE';
    exit;
}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->query(
        'SELECT id, cmd FROM commandes WHERE processed = 0 ORDER BY created_at ASC, id ASC LIMIT 1 FOR UPDATE'
    );
    $row = $stmt->fetch();

    header('Content-Type: text/plain; charset=utf-8');

    if (!$row) {
        echo 'NONE';
        $pdo->commit();
        exit;
    }

    $update = $pdo->prepare(
        'UPDATE commandes SET processed = 1, processed_at = NOW() WHERE id = :id'
    );
    $update->execute([':id' => $row['id']]);

    $pdo->commit();
    echo strtoupper($row['cmd']);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'NONE';
}
