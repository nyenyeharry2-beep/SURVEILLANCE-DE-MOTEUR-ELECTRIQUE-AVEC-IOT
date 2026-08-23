<?php
/**
 * Page de diagnostic - supprimez ce fichier après installation
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo '<h1>Kyrios My Boutique - Diagnostic</h1>';
echo '<p>PHP version: <strong>' . phpversion() . '</strong></p>';

$checks = [];

// .env
$envPath = __DIR__ . '/.env';
$checks['Fichier .env'] = file_exists($envPath) ? 'OK (' . $envPath . ')' : 'MANQUANT - uploadez .env à la racine htdocs';

// Config
try {
    $config = require __DIR__ . '/config/config.php';
    $checks['Configuration'] = 'OK';
    $checks['DB Host'] = $config['db']['host'];
    $checks['DB Name'] = $config['db']['name'];
    $checks['DB User'] = $config['db']['user'];
    $checks['DB Pass'] = empty($config['db']['pass']) ? 'VIDE!' : 'OK (configuré)';
} catch (Exception $e) {
    $checks['Configuration'] = 'ERREUR: ' . $e->getMessage();
}

// PDO
try {
    require_once __DIR__ . '/src/Database.php';
    $db = Kyrios\Database::getInstance($config['db']);
    $checks['Connexion MySQL'] = 'OK';
    $count = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $checks['Utilisateurs en base'] = $count . ' compte(s)';
} catch (Exception $e) {
    $checks['Connexion MySQL'] = 'ERREUR: ' . $e->getMessage();
}

// Fichiers
$checks['index.php'] = file_exists(__DIR__ . '/index.php') ? 'OK' : 'MANQUANT';
$checks['bootstrap.php'] = file_exists(__DIR__ . '/bootstrap.php') ? 'OK' : 'MANQUANT';

echo '<table border="1" cellpadding="8" style="border-collapse:collapse;margin-top:16px">';
echo '<tr><th>Test</th><th>Résultat</th></tr>';
foreach ($checks as $name => $result) {
    $color = (strpos($result, 'ERREUR') !== false || strpos($result, 'MANQUANT') !== false || strpos($result, 'VIDE') !== false) ? 'red' : 'green';
    echo '<tr><td>' . htmlspecialchars($name) . '</td><td style="color:' . $color . '">' . htmlspecialchars($result) . '</td></tr>';
}
echo '</table>';

echo '<p style="margin-top:20px"><a href="/login.php">→ Aller à la page de connexion</a></p>';
echo '<p><small>Supprimez install-check.php après vérification.</small></p>';
