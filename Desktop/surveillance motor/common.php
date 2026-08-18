<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;

  if (DB_PASS === 'COLLEZ_MOT_DE_PASSE_MYSQL' || DB_PASS === '') {
    json_error(
      'Collez le mot de passe MySQL dans config.php (File Manager InfinityFree).',
      500
    );
  }

  try {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER,
      DB_PASS,
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]
    );
  } catch (PDOException $e) {
    json_error('Connexion MySQL impossible : ' . $e->getMessage(), 500);
  }

  return $pdo;
}

function json_ok($data = [], int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function json_error(string $message, int $code = 400): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json(): array {
  static $cached = null;
  if ($cached !== null) return $cached;

  $data = [];
  $raw = file_get_contents('php://input');
  if (is_string($raw) && $raw !== '') {
    $json = json_decode($raw, true);
    if (is_array($json)) {
      $data = $json;
    } else {
      $parsed = [];
      parse_str($raw, $parsed);
      if (is_array($parsed)) $data = $parsed;
    }
  }
  if (!empty($_POST) && is_array($_POST)) {
    $data = array_merge($data, $_POST);
  }
  $cached = $data;
  return $cached;
}

function body_password(array $body): string {
  $plain = normalize_login_password((string) ($body['password'] ?? ''));
  if ($plain !== '') {
    return $plain;
  }

  $b64 = trim((string) ($body['passwordB64'] ?? ''));
  if ($b64 !== '') {
    $decoded = base64_decode(strtr($b64, '-_', '+/'), true);
    if ($decoded === false) {
      $decoded = base64_decode($b64);
    }
    if (is_string($decoded) && $decoded !== '') {
      return normalize_login_password($decoded);
    }
  }

  return '';
}

function normalize_login_password(string $password): string {
  $password = trim($password);
  $password = str_replace(
    ["\u{2018}", "\u{2019}", "\u{201A}", "\u{FF07}", '`'],
    "'",
    $password
  );
  if (function_exists('normalizer_normalize')) {
    $nfc = normalizer_normalize($password, Normalizer::FORM_C);
    if (is_string($nfc) && $nfc !== '') {
      $password = $nfc;
    }
  }
  return $password;
}

function passwords_equal(string $posted, string $expected): bool {
  $posted = normalize_login_password($posted);
  $expected = normalize_login_password($expected);
  if (hash_equals($expected, $posted)) {
    return true;
  }
  return hash_equals($expected, html_entity_decode($posted, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function store_password_hash(string $password): string {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  if (!is_string($hash) || strlen($hash) < 20) {
    return 'sha256:' . hash('sha256', 'lumen|' . $password);
  }
  return $hash;
}

function verify_stored_password(string $password, string $hash): bool {
  $hash = (string) $hash;
  if ($hash === '') {
    return false;
  }
  if (strpos($hash, 'sha256:') === 0) {
    return hash_equals($hash, 'sha256:' . hash('sha256', 'lumen|' . $password));
  }
  if (strlen($hash) < 20) {
    return false;
  }
  return password_verify($password, $hash);
}

function auth_secret(): string {
  return hash('sha256', DB_USER . '|' . DB_PASS . '|' . DEVICE_KEY . '|lumen-auth');
}

function is_https(): bool {
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
  $fwd = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
  return $fwd === 'https';
}

function cookie_options(int $expires): array {
  return [
    'expires' => $expires,
    'path' => '/',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ];
}

function session_cookie_options(): array {
  return [
    'lifetime' => 0,
    'path' => '/',
    'secure' => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
  ];
}

function set_auth_cookie(array $user): void {
  $payload = json_encode([
    'id' => (int) $user['id'],
    'nom' => (string) $user['nom'],
    'email' => (string) $user['email'],
    'exp' => time() + 86400 * 30,
  ], JSON_UNESCAPED_UNICODE);
  $token = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=')
    . '.'
    . hash_hmac('sha256', $payload, auth_secret());
  setcookie('lumen_auth', $token, cookie_options(time() + 86400 * 30));
  $_COOKIE['lumen_auth'] = $token;
}

function clear_auth_cookie(): void {
  setcookie('lumen_auth', '', cookie_options(time() - 42000));
  unset($_COOKIE['lumen_auth']);
}

function user_from_auth_cookie(): ?array {
  $token = (string) ($_COOKIE['lumen_auth'] ?? '');
  if ($token === '' || strpos($token, '.') === false) return null;
  [$payloadB64, $sig] = explode('.', $token, 2);
  $payloadJson = base64_decode(strtr($payloadB64, '-_', '+/'));
  if (!is_string($payloadJson) || $payloadJson === '') return null;
  $expected = hash_hmac('sha256', $payloadJson, auth_secret());
  if (!hash_equals($expected, $sig)) return null;
  $data = json_decode($payloadJson, true);
  if (!is_array($data) || ($data['exp'] ?? 0) < time()) return null;
  if (empty($data['id']) || empty($data['email'])) return null;
  return [
    'id' => (int) $data['id'],
    'nom' => (string) ($data['nom'] ?? ''),
    'email' => (string) $data['email'],
  ];
}

function start_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  $dir = __DIR__ . '/.sessions';
  if (!is_dir($dir)) {
    @mkdir($dir, 0700, true);
  }
  if (is_dir($dir) && is_writable($dir)) {
    session_save_path($dir);
  }

  session_set_cookie_params(session_cookie_options());
  session_start();
}

function sign_in(array $user): array {
  start_session();
  $safe = [
    'id' => (int) $user['id'],
    'nom' => (string) $user['nom'],
    'email' => (string) $user['email'],
  ];
  $_SESSION['user'] = $safe;
  set_auth_cookie($safe);
  return $safe;
}

function current_user(): ?array {
  start_session();
  if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
    return $_SESSION['user'];
  }
  $fromCookie = user_from_auth_cookie();
  if ($fromCookie) {
    $_SESSION['user'] = $fromCookie;
    return $fromCookie;
  }
  return null;
}

function require_user(): array {
  $user = current_user();
  if (!$user) json_error('Non connecté.', 401);
  return $user;
}

function require_device(): void {
  $key = $_SERVER['HTTP_X_DEVICE_KEY'] ?? ($_GET['key'] ?? '');
  $body = read_json();
  if ($key === '' && isset($body['key'])) $key = (string) $body['key'];
  if (!hash_equals(DEVICE_KEY, (string) $key)) {
    json_error('Clé appareil invalide.', 403);
  }
}

function find_user_by_email(PDO $pdo, string $email): ?array {
  $stmt = $pdo->prepare(
    'SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE LOWER(email) = LOWER(?) LIMIT 1'
  );
  $stmt->execute([$email]);
  $row = $stmt->fetch();
  return $row ?: null;
}

function ensure_default_user(PDO $pdo): void {
  $email = strtolower(APP_EMAIL);
  $hash = store_password_hash(APP_PASSWORD);
  $row = find_user_by_email($pdo, $email);

  if (!$row) {
    $ins = $pdo->prepare(
      'INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)'
    );
    $ins->execute([APP_NOM, $email, $hash]);
    return;
  }

  $upd = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, nom = ?, email = ? WHERE id = ?');
  $upd->execute([$hash, APP_NOM, $email, (int) $row['id']]);
}

function ensure_schema(PDO $pdo): void {
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS utilisateurs (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nom VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL UNIQUE,
      mot_de_passe VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS moteur_live (
      id VARCHAR(32) PRIMARY KEY,
      vibrationX DOUBLE NOT NULL DEFAULT 0,
      vibrationY DOUBLE NOT NULL DEFAULT 0,
      vibrationZ DOUBLE NOT NULL DEFAULT 0,
      x DOUBLE NOT NULL DEFAULT 0,
      y DOUBLE NOT NULL DEFAULT 0,
      z DOUBLE NOT NULL DEFAULT 0,
      rpm DOUBLE NOT NULL DEFAULT 0,
      rmsMmS DOUBLE NOT NULL DEFAULT 0,
      uniteRms VARCHAR(16) NOT NULL DEFAULT 'mm/s',
      defautCapteur TINYINT(1) NOT NULL DEFAULT 0,
      etatMoteur VARCHAR(64) NOT NULL DEFAULT 'arrêté',
      timestamp DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS mesures (
      id INT AUTO_INCREMENT PRIMARY KEY,
      moteur_id VARCHAR(32) NOT NULL DEFAULT 'moteur_01',
      vibrationX DOUBLE NOT NULL DEFAULT 0,
      vibrationY DOUBLE NOT NULL DEFAULT 0,
      vibrationZ DOUBLE NOT NULL DEFAULT 0,
      x DOUBLE NOT NULL DEFAULT 0,
      y DOUBLE NOT NULL DEFAULT 0,
      z DOUBLE NOT NULL DEFAULT 0,
      rpm DOUBLE NOT NULL DEFAULT 0,
      rmsMmS DOUBLE NOT NULL DEFAULT 0,
      uniteRms VARCHAR(16) NOT NULL DEFAULT 'mm/s',
      defautCapteur TINYINT(1) NOT NULL DEFAULT 0,
      etatMoteur VARCHAR(64) NOT NULL DEFAULT 'arrêté',
      timestamp DATETIME NOT NULL,
      INDEX idx_moteur_time (moteur_id, timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $pdo->exec("
    CREATE TABLE IF NOT EXISTS commande (
      id VARCHAR(32) PRIMARY KEY,
      etatCommande TINYINT(1) NOT NULL DEFAULT 0,
      userId INT NULL,
      updated_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  ");

  $stmt = $pdo->prepare('INSERT IGNORE INTO moteur_live (id) VALUES (?)');
  $stmt->execute([MOTEUR_ID]);

  $stmt = $pdo->prepare('INSERT IGNORE INTO commande (id, etatCommande, updated_at) VALUES (?, 0, NOW())');
  $stmt->execute(['moteur']);

  try {
    $pdo->exec('ALTER TABLE utilisateurs MODIFY mot_de_passe VARCHAR(255) NOT NULL');
  } catch (Throwable $e) {
    /* colonne déjà à la bonne taille, ou droits limités */
  }

  ensure_default_user($pdo);
}

/* ——— Miroir Firebase (tableau de bord Web/) ——— */

function firebase_configured(): bool {
  return defined('FIREBASE_DB_URL')
    && FIREBASE_DB_URL !== ''
    && defined('FIREBASE_AUTH')
    && FIREBASE_AUTH !== ''
    && strpos(FIREBASE_DB_URL, 'VOTRE_PROJET') === false;
}

function map_lumen_etat_to_status(string $etat): string {
  $e = mb_strtolower(trim($etat));
  if (strpos($e, 'alarm') !== false) return 'ALARME';
  if (strpos($e, 'marche') !== false || strpos($e, 'on') !== false) return 'NORMAL';
  if (strpos($e, 'arr') !== false || strpos($e, 'stop') !== false) return 'ARRET';
  return 'SURVEILLANCE';
}

function firebase_rtdb_put(string $path, array $payload): void {
  if (!firebase_configured()) return;

  $url = rtrim(FIREBASE_DB_URL, '/')
    . '/' . ltrim($path, '/')
    . '.json?auth=' . urlencode(FIREBASE_AUTH);

  $ctx = stream_context_create([
    'http' => [
      'method' => 'PUT',
      'header' => "Content-Type: application/json\r\n",
      'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
      'timeout' => 4,
      'ignore_errors' => true,
    ],
  ]);

  @file_get_contents($url, false, $ctx);
}

function firebase_sync_live_from_lumen(
  float $x,
  float $y,
  float $z,
  float $rpm,
  float $rms,
  string $etat,
  bool $defaut
): void {
  if (!firebase_configured()) return;

  $status = map_lumen_etat_to_status($etat);
  $a_rms = sqrt($x * $x + $y * $y + $z * $z) * 9.80665;

  firebase_rtdb_put('moteur/live', [
    'ax' => round($x, 4),
    'ay' => round($y, 4),
    'az' => round($z, 4),
    'a_rms' => round($a_rms, 3),
    'vibration_rms' => round($rms, 4),
    'rpm' => round($rpm, 1),
    'status' => $status,
    'alert_level' => $status === 'ALARME' ? 'ALARME' : ($status === 'ARRET' ? 'INFO' : 'NORMAL'),
    'diagnostic' => $defaut ? 'Defaut capteur Lumen' : 'Donnees Lumen ESP32',
    'relay_state' => strpos(mb_strtolower($etat), 'marche') !== false,
    'online' => true,
    'uno_online' => true,
    'gateway' => 'ESP32_Lumen',
    'controller' => 'Lumen',
    'timestamp' => (int) round(microtime(true) * 1000),
    'unit_a_rms' => 'm/s2',
    'unit_vibration_rms' => 'mm/s',
  ]);
}

function firebase_sync_historique_from_lumen(
  float $x,
  float $y,
  float $z,
  float $rpm,
  float $rms,
  string $etat
): void {
  if (!firebase_configured()) return;

  $status = map_lumen_etat_to_status($etat);
  $a_rms = sqrt($x * $x + $y * $y + $z * $z) * 9.80665;
  $id = (string) (int) round(microtime(true) * 1000);

  firebase_rtdb_put('moteur/historique/' . $id, [
    'timestamp' => (int) round(microtime(true)),
    'epoch_ms' => (int) round(microtime(true) * 1000),
    'vibration_rms' => round($rms, 4),
    'a_rms' => round($a_rms, 3),
    'rpm' => round($rpm, 1),
    'ax' => round($x, 4),
    'ay' => round($y, 4),
    'az' => round($z, 4),
    'status' => $status,
    'diagnostic' => 'Historique Lumen',
    'relay_state' => strpos(mb_strtolower($etat), 'marche') !== false,
  ]);
}
