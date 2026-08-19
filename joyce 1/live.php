<?php
require_once __DIR__ . '/common.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  json_ok(['ok' => true]);
}

$pdo = db();
ensure_schema($pdo);

$live = $pdo->prepare('SELECT * FROM moteur_live WHERE id = ? LIMIT 1');
$live->execute([MOTEUR_ID]);
$row = $live->fetch() ?: [];

$hist = $pdo->prepare(
  'SELECT * FROM mesures WHERE moteur_id = ? ORDER BY timestamp DESC, id DESC LIMIT 40'
);
$hist->execute([MOTEUR_ID]);
$historique = [];
foreach (array_reverse($hist->fetchAll()) as $h) {
  $historique[] = row_to_historique($h);
}

json_ok([
  'ok' => true,
  'live' => row_to_live($row),
  'historique' => $historique,
]);
