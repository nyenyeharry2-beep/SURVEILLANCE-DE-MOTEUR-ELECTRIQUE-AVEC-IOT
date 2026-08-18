<?php
require_once __DIR__ . '/common.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  json_ok(['ok' => true]);
}

$pdo = db();
ensure_schema($pdo);
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
  json_ok(['ok' => true, 'config' => fetch_moteur_config($pdo)]);
}

if ($method === 'POST') {
  $body = read_json();
  $cfg = fetch_moteur_config($pdo);
  $fields = [
    'rpm_nominal', 'rpm_min', 'rpm_max',
    'vib_normal_mms', 'vib_alerte_mms', 'vib_critique_mms',
    'a_rms_normal_ms2', 'a_rms_alerte_ms2', 'a_rms_critique_ms2',
  ];
  foreach ($fields as $f) {
    if (isset($body[$f])) $cfg[$f] = (float) $body[$f];
  }
  if (isset($body['auto_stop_on_alarm'])) $cfg['auto_stop_on_alarm'] = !empty($body['auto_stop_on_alarm']);
  if (isset($body['buzzer_enabled'])) $cfg['buzzer_enabled'] = !empty($body['buzzer_enabled']);
  if (isset($body['note'])) $cfg['note'] = (string) $body['note'];

  $stmt = $pdo->prepare(
    'UPDATE moteur_config SET
      rpm_nominal=?, rpm_min=?, rpm_max=?,
      vib_normal_mms=?, vib_alerte_mms=?, vib_critique_mms=?,
      a_rms_normal_ms2=?, a_rms_alerte_ms2=?, a_rms_critique_ms2=?,
      auto_stop_on_alarm=?, buzzer_enabled=?, note=?, updated_at=NOW()
     WHERE id=?'
  );
  $stmt->execute([
    $cfg['rpm_nominal'], $cfg['rpm_min'], $cfg['rpm_max'],
    $cfg['vib_normal_mms'], $cfg['vib_alerte_mms'], $cfg['vib_critique_mms'],
    $cfg['a_rms_normal_ms2'], $cfg['a_rms_alerte_ms2'], $cfg['a_rms_critique_ms2'],
    $cfg['auto_stop_on_alarm'] ? 1 : 0,
    $cfg['buzzer_enabled'] ? 1 : 0,
    $cfg['note'] ?? '',
    'default',
  ]);
  json_ok(['ok' => true, 'config' => $cfg]);
}

json_error('Méthode non autorisée.', 405);
