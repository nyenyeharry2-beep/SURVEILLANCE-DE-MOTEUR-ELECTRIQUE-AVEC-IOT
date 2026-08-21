<?php
/**
 * Configuration base de donnees InfinityFree
 * A deployer dans htdocs/ sur le File Manager InfinityFree
 */

define('DB_HOST', 'sql201.infinityfree.com');
define('DB_NAME', 'if0_42713537_surveillancemoteurharry');
define('DB_USER', 'if0_42713537');
define('DB_PASS', 'wjHZN8YDlhqw0j');

// Cle API pour securiser les requetes ESP32
define('API_KEY', 'harry_surveillance_2026');

// Afficher le detail des erreurs SQL (mettre false en production)
define('DEBUG_DB', true);

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

/**
 * Connexion PDO securisee (compatible InfinityFree)
 * Important : pas d'espace dans le DSN, pas de port
 */
function getDbConnection(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

/**
 * Cree les tables si elles n'existent pas encore
 */
function ensureDatabaseSchema(): void
{
    $pdo = getDbConnection();

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS moteur_surveillance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date_mesure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ax FLOAT NOT NULL,
            ay FLOAT NOT NULL,
            az FLOAT NOT NULL,
            rpm FLOAT NOT NULL,
            arms FLOAT NOT NULL,
            vrms FLOAT NOT NULL,
            ecart FLOAT NOT NULL,
            etat VARCHAR(20) NOT NULL,
            relay_state VARCHAR(3) NOT NULL,
            anomalie_vibration TINYINT(1) NOT NULL DEFAULT 0,
            anomalie_vitesse TINYINT(1) NOT NULL DEFAULT 0,
            INDEX idx_date_mesure (date_mesure)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS commandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cmd VARCHAR(3) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            processed TINYINT(1) NOT NULL DEFAULT 0,
            processed_at DATETIME NULL,
            INDEX idx_processed (processed, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS etat_relais (
            id INT PRIMARY KEY,
            relay_state VARCHAR(3) NOT NULL DEFAULT 'OFF',
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    $stmt = $pdo->query('SELECT COUNT(*) AS n FROM etat_relais WHERE id = 1');
    $row = $stmt->fetch();
    if ((int) $row['n'] === 0) {
        $pdo->exec("INSERT INTO etat_relais (id, relay_state) VALUES (1, 'OFF')");
    }
}

/**
 * Detecte le nom de la colonne date (timestamp ou date_mesure)
 */
function getMesureDateColumn(PDO $pdo): string
{
    static $col = null;
    if ($col !== null) {
        return $col;
    }

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM moteur_surveillance LIKE 'date_mesure'");
        if ($stmt->fetch()) {
            $col = 'date_mesure';
            return $col;
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM moteur_surveillance LIKE 'timestamp'");
        if ($stmt->fetch()) {
            $col = 'timestamp';
            return $col;
        }
    } catch (PDOException $e) {
        // Table pas encore creee
    }

    $col = 'date_mesure';
    return $col;
}

/**
 * Retourne la date d'une ligne de mesure
 */
function getMesureDate(array $row): string
{
    return $row['date_mesure'] ?? $row['timestamp'] ?? '';
}

/**
 * Message d'erreur lisible pour le dashboard
 */
function getDbErrorMessage(PDOException $e): string
{
    if (!DEBUG_DB) {
        return 'Impossible de lire la base de donnees.';
    }

    $msg = $e->getMessage();
    if (strpos($msg, '1045') !== false) {
        return 'Acces refuse : verifiez DB_USER et DB_PASS dans config.php';
    }
    if (strpos($msg, '1049') !== false) {
        return 'Base introuvable : verifiez DB_NAME dans config.php';
    }
    if (strpos($msg, '2002') !== false || strpos($msg, 'No such file') !== false) {
        return 'Connexion impossible : verifiez DB_HOST (doit etre sql201.infinityfree.com, pas localhost)';
    }
    if (strpos($msg, '1146') !== false) {
        return 'Table manquante : ouvrez install.php pour creer les tables automatiquement';
    }
    return 'Erreur SQL : ' . $msg;
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
