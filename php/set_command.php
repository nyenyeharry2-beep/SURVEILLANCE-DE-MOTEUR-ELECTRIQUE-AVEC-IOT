<?php
/**
 * set_command.php - Enregistre une commande ON/OFF (GET)
 * URL: set_command.php?cmd=ON ou set_command.php?cmd=OFF
 */
require_once __DIR__ . '/config.php';

$cmd = strtoupper(trim((string) ($_GET['cmd'] ?? '')));

if (!in_array($cmd, ['ON', 'OFF'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'Commande invalide. Utilisez cmd=ON ou cmd=OFF'], 400);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('INSERT INTO commandes (cmd) VALUES (:cmd)');
    $stmt->execute([':cmd' => $cmd]);

    jsonResponse(['status' => 'ok', 'cmd' => $cmd, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    jsonResponse(['status' => 'error', 'message' => 'Erreur base de donnees'], 500);
}
