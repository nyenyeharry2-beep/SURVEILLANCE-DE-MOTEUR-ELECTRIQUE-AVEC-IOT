<?php
/**
 * Test connexion MySQL - Supprimez ce fichier après diagnostic
 * Ouvrez : https://genies.free.je/test_db.php
 */
header('Content-Type: text/html; charset=utf-8');

$configFile = __DIR__ . '/config/database.php';
if (!file_exists($configFile)) {
    die('<h2 style="color:red;">❌ Fichier config/database.php introuvable</h2>');
}

require_once $configFile;

echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Test MySQL</title>';
echo '<style>body{font-family:Arial;max-width:700px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} code{background:#f4f4f4;padding:2px 6px;}</style></head><body>';
echo '<h1>Test connexion MySQL</h1>';

echo '<h3>Configuration lue :</h3><ul>';
echo '<li>Host : <code>' . htmlspecialchars(DB_HOST) . '</code></li>';
echo '<li>Port : <code>' . htmlspecialchars(DB_PORT) . '</code></li>';
echo '<li>Base : <code>' . htmlspecialchars(DB_NAME) . '</code></li>';
echo '<li>User : <code>' . htmlspecialchars(DB_USER) . '</code></li>';
echo '<li>Pass : <code>' . (DB_PASS ? '****' . substr(DB_PASS, -4) : 'VIDE') . '</code></li>';
echo '<li>BASE_URL : <code>' . htmlspecialchars(BASE_URL) . '</code></li>';
echo '</ul>';

// Test 1 : PDO
echo '<h3>Test PDO :</h3>';
try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo '<p class="ok">✅ Connexion PDO réussie !</p>';

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo '<p>Tables trouvées (' . count($tables) . ') :</p><ul>';
    foreach ($tables as $t) {
        echo '<li>' . htmlspecialchars($t) . '</li>';
    }
    echo '</ul>';

    if (count($tables) === 0) {
        echo '<p class="err">⚠️ Base vide ! Importez <code>database/database.sql</code> via phpMyAdmin.</p>';
    } elseif (!in_array('students', $tables)) {
        echo '<p class="err">⚠️ Tables manquantes ! Réimportez <code>database/database.sql</code>.</p>';
    } else {
        echo '<p class="ok">✅ Base de données OK ! <a href="admin/login.php">Aller à l\'admin</a> | <a href="inscription.php">Inscription</a></p>';
    }
} catch (PDOException $e) {
    echo '<p class="err">❌ Erreur PDO : ' . htmlspecialchars($e->getMessage()) . '</p>';

    echo '<h3>Solutions :</h3><ol>';
    echo '<li>Vérifiez le mot de passe MySQL dans <strong>InfinityFree → MySQL Databases</strong></li>';
    echo '<li>Modifiez <code>config/database.php</code> avec le bon mot de passe</li>';
    echo '<li>Vérifiez que la base <code>' . htmlspecialchars(DB_NAME) . '</code> existe</li>';
    echo '<li>Importez <code>database/database.sql</code> via phpMyAdmin</li>';
    echo '<li>Vérifiez le hostname MySQL (peut être sql205, sql301, etc.)</li>';
    echo '</ol>';
}

// Test 2 : mysqli (fallback)
echo '<h3>Test mysqli :</h3>';
if (function_exists('mysqli_connect')) {
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
    if ($mysqli->connect_error) {
        echo '<p class="err">❌ mysqli : ' . htmlspecialchars($mysqli->connect_error) . '</p>';
    } else {
        echo '<p class="ok">✅ Connexion mysqli réussie !</p>';
        $mysqli->close();
    }
} else {
    echo '<p class="err">mysqli non disponible</p>';
}

echo '<hr><p><small>Supprimez ce fichier (test_db.php) après résolution du problème.</small></p>';
echo '</body></html>';
