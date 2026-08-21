<?php
/**
 * test_db.php - Diagnostic connexion MySQL
 * Ouvrir : http://surveillancemoteurharry.ct.ws/test_db.php
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Test connexion MySQL InfinityFree ===\n\n";
echo "Host : " . DB_HOST . "\n";
echo "Base : " . DB_NAME . "\n";
echo "User : " . DB_USER . "\n\n";

try {
    $pdo = getDbConnection();
    echo "OK : Connexion PDO reussie\n\n";

    ensureDatabaseSchema();
    echo "OK : Tables verifiees/crees\n\n";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables presentes :\n";
    foreach ($tables as $t) {
        echo "  - $t\n";
    }

    $count = $pdo->query('SELECT COUNT(*) FROM moteur_surveillance')->fetchColumn();
    echo "\nNombre de mesures : $count\n";
    echo "\nTout fonctionne. Ouvrez dashboard.php\n";
} catch (PDOException $e) {
    echo "ERREUR :\n";
    echo getDbErrorMessage($e) . "\n\n";
    echo "Detail : " . $e->getMessage() . "\n";
}
