<?php
/** Connexion stockage invités — usage interne API uniquement */

function nkuba_config(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require dirname(__DIR__) . '/_private/app.secrets.php';
    }
    return $cfg;
}

function nkuba_pdo(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $s = nkuba_config()['storage'];
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $s['host'],
        (int)$s['port'],
        $s['name']
    );
    $pdo = new PDO($dsn, $s['user'], $s['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function nkuba_json_headers(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}

function nkuba_upload_dir(): string {
    $dir = dirname(__DIR__) . '/assets/uploads';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function nkuba_assets_dir(): string {
    return dirname(__DIR__) . '/assets';
}

function nkuba_get_config(string $key, string $default = ''): string {
    try {
        $st = nkuba_pdo()->prepare('SELECT config_value FROM event_config WHERE config_key = ?');
        $st->execute([$key]);
        $row = $st->fetch();
        return $row ? (string)$row['config_value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function nkuba_set_config(string $key, string $value): void {
    $st = nkuba_pdo()->prepare(
        'INSERT INTO event_config (config_key, config_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)'
    );
    $st->execute([$key, $value]);
}

function nkuba_guest_row_to_array(array $row): array {
    return [
        'id' => (int)$row['id'],
        'fullName' => $row['full_name'],
        'whatsapp' => $row['whatsapp'],
        'tableZone' => $row['table_zone'],
        'seats' => (int)$row['seats'],
        'styleId' => $row['style_id'],
        'sent' => (bool)$row['sent'],
        'createdAt' => $row['created_at'],
    ];
}
