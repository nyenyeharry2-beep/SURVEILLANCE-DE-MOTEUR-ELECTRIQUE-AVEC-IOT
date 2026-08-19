<?php
require_once __DIR__ . '/common.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Device-Key');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  json_ok(['ok' => true]);
}

$pdo = db();
ensure_schema($pdo);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
  require_device();
  $row = $pdo->query("SELECT * FROM commande WHERE id = 'moteur' LIMIT 1")->fetch() ?: [];
  $relay = isset($row['relay']) ? (bool) $row['relay'] : (bool) ($row['etatCommande'] ?? 0);
  json_ok([
    'ok' => true,
    'relay' => $relay,
    'buzzer_mute' => (bool) ($row['buzzer_mute'] ?? 0),
    'etatCommande' => $relay,
    'updatedAt' => $row['updated_at'] ?? null,
    'config' => fetch_moteur_config($pdo),
  ]);
}

if ($method === 'POST') {
  $body = read_json();
  $relay = null;
  if (array_key_exists('relay', $body)) $relay = !empty($body['relay']);
  if (array_key_exists('etatCommande', $body)) $relay = !empty($body['etatCommande']);
  $mute = array_key_exists('buzzer_mute', $body) ? !empty($body['buzzer_mute']) : null;

  $row = $pdo->query("SELECT relay, buzzer_mute, etatCommande FROM commande WHERE id = 'moteur' LIMIT 1")->fetch();
  if ($relay === null) {
    $relay = isset($row['relay']) ? (bool) $row['relay'] : (bool) ($row['etatCommande'] ?? 0);
  }
  if ($mute === null) {
    $mute = (bool) ($row['buzzer_mute'] ?? 0);
  }

  $stmt = $pdo->prepare(
    'UPDATE commande SET etatCommande=?, relay=?, buzzer_mute=?, updated_at=NOW() WHERE id=?'
  );
  $stmt->execute([$relay ? 1 : 0, $relay ? 1 : 0, $mute ? 1 : 0, 'moteur']);

  if ($stmt->rowCount() === 0) {
    $ins = $pdo->prepare(
      'INSERT INTO commande (id, etatCommande, relay, buzzer_mute, updated_at) VALUES (?, ?, ?, ?, NOW())'
    );
    $ins->execute(['moteur', $relay ? 1 : 0, $relay ? 1 : 0, $mute ? 1 : 0]);
  }

  json_ok(['ok' => true, 'relay' => $relay, 'buzzer_mute' => $mute]);
}

json_error('Méthode non autorisée.', 405);
