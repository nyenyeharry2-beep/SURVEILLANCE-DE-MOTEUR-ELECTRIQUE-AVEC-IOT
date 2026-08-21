<?php
/**
 * Configuration base de donnees InfinityFree
 * A deployer dans htdocs/ sur le File Manager InfinityFree
 */

define('DB_HOST', 'sql201.infinityfree.com');
define('DB_PORT', 3306);
define('DB_NAME', 'if0_42713537_surveillancemoteurharry');
define('DB_USER', 'if0_42713537');
define('DB_PASS', 'wjHZN8YDlhqw0j');

// Cle API pour securiser les requetes ESP32
define('API_KEY', 'harry_surveillance_2026');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

/**
 * Connexion PDO securisee
 */
function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * Verification cle API
 */
function checkApiKey(): bool
{
    $key = $_GET['api_key'] ?? $_POST['api_key'] ?? ($_SERVER['HTTP_X_API_KEY'] ?? '');
    return hash_equals(API_KEY, (string) $key);
}

/**
 * Reponse JSON standard
 */
function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
