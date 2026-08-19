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
    'hint' => 'POST JSON Uno+ESP32 (X-Device-Key). Champs: ax,ay,az,a_rms,vibration_rms,rpm,status,...',
  ]);
}

require_device();
$pdo = db();
ensure_schema($pdo);
$body = read_json();

$ax = (float) ($body['ax'] ?? $body['x'] ?? $body['vibrationX'] ?? 0);
$ay = (float) ($body['ay'] ?? $body['y'] ?? $body['vibrationY'] ?? 0);
$az = (float) ($body['az'] ?? $body['z'] ?? $body['vibrationZ'] ?? 0);
$aRms = (float) ($body['a_rms'] ?? 0);
$vibRms = (float) ($body['vibration_rms'] ?? $body['rmsMmS'] ?? 0);
$rpm = (float) ($body['rpm'] ?? 0);
$rpmNom = (float) ($body['rpm_nominal'] ?? 1500);
$status = (string) ($body['status'] ?? $body['status_moteur'] ?? 'INCONNU');
$alert = (string) ($body['alert_level'] ?? 'INFO');
$diag = (string) ($body['diagnostic'] ?? '');
$hint = (string) ($body['anomaly_hint'] ?? '');
$relay = !empty($body['relay_state']) ? 1 : 0;
$buzzer = !empty($body['buzzer_state']) ? 1 : 0;
$mute = !empty($body['buzzer_mute']) ? 1 : 0;
$unoOn = !empty($body['uno_online']) ? 1 : 0;
$espTs = (int) ($body['timestamp'] ?? 0);
$defaut = !empty($body['defautCapteur']) ? 1 : 0;
$etat = (string) ($body['etatMoteur'] ?? $status);
$histo = !empty($body['historique']);

$pdo->beginTransaction();
try {
  $upd = $pdo->prepare(
    'UPDATE moteur_live SET
      vibrationX=?, vibrationY=?, vibrationZ=?, x=?, y=?, z=?,
      rpm=?, rmsMmS=?, uniteRms=?, a_rms=?, vibration_rms=?, rpm_nominal=?,
      status_moteur=?, alert_level=?, diagnostic=?, anomaly_hint=?,
      relay_state=?, buzzer_state=?, buzzer_mute=?, uno_online=?, esp_timestamp=?,
      defautCapteur=?, etatMoteur=?, timestamp=NOW()
     WHERE id=?'
  );
  $upd->execute([
    $ax, $ay, $az, $ax, $ay, $az, $rpm, $vibRms, 'mm/s', $aRms, $vibRms, $rpmNom,
    $status, $alert, $diag, $hint, $relay, $buzzer, $mute, $unoOn, $espTs,
    $defaut, $etat, MOTEUR_ID,
  ]);

  if ($upd->rowCount() === 0) {
    $insLive = $pdo->prepare(
      'INSERT INTO moteur_live
        (id, vibrationX, vibrationY, vibrationZ, x, y, z, rpm, rmsMmS, uniteRms,
         a_rms, vibration_rms, rpm_nominal, status_moteur, alert_level, diagnostic,
         anomaly_hint, relay_state, buzzer_state, buzzer_mute, uno_online, esp_timestamp,
         defautCapteur, etatMoteur, timestamp)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
    );
    $insLive->execute([
      MOTEUR_ID, $ax, $ay, $az, $ax, $ay, $az, $rpm, $vibRms, 'mm/s',
      $aRms, $vibRms, $rpmNom, $status, $alert, $diag, $hint,
      $relay, $buzzer, $mute, $unoOn, $espTs, $defaut, $etat,
    ]);
  }

  if ($histo) {
    $ins = $pdo->prepare(
      'INSERT INTO mesures
        (moteur_id, vibrationX, vibrationY, vibrationZ, x, y, z, rpm, rmsMmS, uniteRms,
         a_rms, vibration_rms, status_moteur, diagnostic, relay_state,
         defautCapteur, etatMoteur, timestamp)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
    );
    $ins->execute([
      MOTEUR_ID, $ax, $ay, $az, $ax, $ay, $az, $rpm, $vibRms, 'mm/s',
      $aRms, $vibRms, $status, $diag, $relay, $defaut, $etat,
    ]);
  }

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  json_error($e->getMessage(), 500);
}

json_ok(['ok' => true]);
