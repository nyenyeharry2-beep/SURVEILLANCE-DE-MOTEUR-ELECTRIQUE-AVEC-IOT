<?php
/**
 * Connexion PDO à la base de données
 */

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die('<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;border:2px solid #dc3545;border-radius:8px;">'
                    . '<h2 style="color:#dc3545;">Erreur connexion MySQL</h2>'
                    . '<p><strong>Détail :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>'
                    . '<p><strong>Vérifiez :</strong></p><ul>'
                    . '<li>Mot de passe MySQL dans config/database.php</li>'
                    . '<li>Base importée via phpMyAdmin (database.sql)</li>'
                    . '<li>Hostname MySQL dans le panel InfinityFree</li>'
                    . '</ul>'
                    . '<p><a href="test_db.php">→ Ouvrir test_db.php pour diagnostic</a></p>'
                    . '</div>');
            }
            die('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.');
        }
    }
    return $pdo;
}
