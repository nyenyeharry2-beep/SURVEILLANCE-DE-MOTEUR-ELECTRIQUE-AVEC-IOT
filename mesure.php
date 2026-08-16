<?php
require_once __DIR__ . '/common.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  json_error('POST requis.', 405);
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

json_ok(['ok' => true]);
