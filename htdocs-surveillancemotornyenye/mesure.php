<?php
require_once __DIR__ . '/common.php';

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'OPTIONS') {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, X-Device-Key, User-Agent');
  json_ok(['ok' => true]);
}

if ($method !== 'POST') {
  json_ok([
    'ok' => true,
    'endpoint' => 'mesure',
    'firebase' => firebase_configured(),
    'hint' => 'POST JSON vers /mesure.php (clé X-Device-Key). Un GET ici confirme que le fichier existe.',
  ]);
}

require_device();
$pdo = db();
ensure_schema($pdo);
$body = read_json();

$x = (float) ($body['x'] ?? $body['vibrationX'] ?? 0);
$y = (float) ($body['y'] ?? $body['vibrationY'] ?? 0);
$z = (float) ($body['z'] ?? $body['vibrationZ'] ?? 0);
$rpm = (float) ($body['rpm'] ?? 0);
$rms = (float) ($body['rmsMmS'] ?? 0);
$defaut = !empty($body['defautCapteur']) ? 1 : 0;
$etat = (string) ($body['etatMoteur'] ?? 'arrêté');
$histo = !empty($body['historique']);

$pdo->beginTransaction();
try {
  $upd = $pdo->prepare(
    'UPDATE moteur_live SET
      vibrationX=?, vibrationY=?, vibrationZ=?,
      x=?, y=?, z=?, rpm=?, rmsMmS=?, uniteRms=?,
      defautCapteur=?, etatMoteur=?, timestamp=NOW()
     WHERE id=?'
  );
  $upd->execute([$x, $y, $z, $x, $y, $z, $rpm, $rms, 'mm/s', $defaut, $etat, MOTEUR_ID]);

  if ($upd->rowCount() === 0) {
    $insLive = $pdo->prepare(
      'INSERT INTO moteur_live
        (id, vibrationX, vibrationY, vibrationZ, x, y, z, rpm, rmsMmS, uniteRms, defautCapteur, etatMoteur, timestamp)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $insLive->execute([MOTEUR_ID, $x, $y, $z, $x, $y, $z, $rpm, $rms, 'mm/s', $defaut, $etat]);
  }

  if ($histo) {
    $ins = $pdo->prepare(
      'INSERT INTO mesures
        (moteur_id, vibrationX, vibrationY, vibrationZ, x, y, z, rpm, rmsMmS, uniteRms, defautCapteur, etatMoteur, timestamp)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $ins->execute([MOTEUR_ID, $x, $y, $z, $x, $y, $z, $rpm, $rms, 'mm/s', $defaut, $etat]);
  }

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  json_error($e->getMessage(), 500);
}

firebase_sync_live_from_lumen($x, $y, $z, $rpm, $rms, $etat, (bool) $defaut);
if ($histo) {
  firebase_sync_historique_from_lumen($x, $y, $z, $rpm, $rms, $etat);
}

json_ok(['ok' => true, 'firebase' => firebase_configured()]);
