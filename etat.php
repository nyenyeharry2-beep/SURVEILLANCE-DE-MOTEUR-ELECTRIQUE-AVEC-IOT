<?php
require_once __DIR__ . '/common.php';

require_user();
$pdo = db();
ensure_schema($pdo);

$live = $pdo->prepare('SELECT * FROM moteur_live WHERE id = ? LIMIT 1');
$live->execute([MOTEUR_ID]);
$moteur = $live->fetch() ?: null;

$cmd = $pdo->query("SELECT * FROM commande WHERE id = 'moteur' LIMIT 1")->fetch() ?: [
  'etatCommande' => 0,
  'updated_at' => null,
];

$hist = $pdo->prepare(
  'SELECT * FROM mesures WHERE moteur_id = ? ORDER BY timestamp DESC, id DESC LIMIT 40'
);
$hist->execute([MOTEUR_ID]);
$mesures = array_reverse($hist->fetchAll());

json_ok([
  'ok' => true,
  'mysql' => true,
  'moteur' => $moteur,
  'commande' => [
    'etatCommande' => (bool) $cmd['etatCommande'],
    'updatedAt' => $cmd['updated_at'] ?? null,
  ],
  'mesures' => $mesures,
]);
