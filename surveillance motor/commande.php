<?php
require_once __DIR__ . '/common.php';

$pdo = db();
ensure_schema($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  require_device();
  $row = $pdo->query("SELECT etatCommande, updated_at FROM commande WHERE id = 'moteur' LIMIT 1")->fetch();
  json_ok([
    'ok' => true,
    'etatCommande' => (bool) ($row['etatCommande'] ?? 0),
    'updatedAt' => $row['updated_at'] ?? null,
  ]);
}

if ($method === 'POST') {
  $user = require_user();
  $body = read_json();
  $on = !empty($body['etatCommande']);
  $stmt = $pdo->prepare(
    'REPLACE INTO commande (id, etatCommande, userId, updated_at) VALUES (?, ?, ?, NOW())'
  );
  $stmt->execute(['moteur', $on ? 1 : 0, $user['id']]);
  json_ok(['ok' => true, 'etatCommande' => $on]);
}

json_error('Méthode non autorisée.', 405);
