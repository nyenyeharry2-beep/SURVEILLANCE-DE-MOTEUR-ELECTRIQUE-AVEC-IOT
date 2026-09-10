<?php
/**
 * Test connexion MySQL - Supprimez ce fichier après diagnostic
 * Ouvrez : https://genies.free.je/test_db.php
 */
header('Content-Type: text/html; charset=utf-8');

$configFile = __DIR__ . '/config/database.php';
if (!file_exists($configFile)) {
    die('<h2 style="color:red;">Fichier config/database.php introuvable</h2>');
}

require_once $configFile;

echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Test MySQL</title>';
echo '<style>body{font-family:Arial;max-width:750px;margin:40px auto;padding:20px;line-height:1.5;} .ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;} .warn{color:#b05a00;} code{background:#f4f4f4;padding:2px 6px;border-radius:3px;} pre{background:#f8f8f8;padding:12px;border-radius:6px;overflow-x:auto;font-size:13px;}</style></head><body>';
echo '<h1>Test connexion MySQL</h1>';

$passLen = strlen(DB_PASS);
echo '<h3>Configuration lue :</h3><ul>';
echo '<li>Host : <code>' . htmlspecialchars(DB_HOST) . '</code></li>';
echo '<li>Port : <code>' . htmlspecialchars(DB_PORT) . '</code></li>';
echo '<li>Base : <code>' . htmlspecialchars(DB_NAME) . '</code></li>';
echo '<li>User : <code>' . htmlspecialchars(DB_USER) . '</code></li>';
echo '<li>Pass : <code>' . (DB_PASS ? '****' . substr(DB_PASS, -4) : 'VIDE') . '</code> (' . $passLen . ' caractères)</li>';
echo '<li>BASE_URL : <code>' . htmlspecialchars(BASE_URL) . '</code></li>';
echo '</ul>';

if ($passLen !== 15) {
    echo '<p class="warn">⚠️ Le mot de passe InfinityFree doit faire <strong>15 caractères</strong> (JojYwJiexiP1TAd). Vous en avez ' . $passLen . ' — vérifiez les espaces ou caractères en trop.</p>';
}

if (strpos(DB_HOST, 'sql1205') !== false) {
    echo '<p class="err">❌ Erreur hostname : <code>sql1205</code> est incorrect. Utilisez <code>sql205.infinityfree.com</code></p>';
}

function tryConnect(string $label, string $host, string $user, string $pass, ?string $db = null): void
{
    echo '<h4>' . htmlspecialchars($label) . '</h4>';
    try {
        $dsn = 'mysql:host=' . $host . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
        if ($db) {
            $dsn .= ';dbname=' . $db;
        }
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '<p class="ok">✅ Connexion réussie</p>';
        if ($db) {
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            echo '<p>Tables : ' . count($tables) . '</p>';
            if (count($tables) === 0) {
                echo '<p class="warn">⚠️ Base vide → importez <code>database/database_donnees.sql</code> dans phpMyAdmin</p>';
            } elseif (in_array('students', $tables, true)) {
                echo '<p class="ok">✅ Application prête ! <a href="admin/login.php">Admin</a> | <a href="inscription.php">Inscription</a></p>';
            }
        }
    } catch (PDOException $e) {
        echo '<p class="err">❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<h3>Tests de connexion :</h3>';
tryConnect('Test 1 — Config actuelle', DB_HOST, DB_USER, DB_PASS, DB_NAME);

echo '<hr><h3>Si erreur 1045 Access denied :</h3>';
echo '<ol>';
echo '<li>InfinityFree → <strong>MySQL Databases</strong> → cliquez <strong>Change Password</strong></li>';
echo '<li>Copiez le NOUVEAU mot de passe</li>';
echo '<li>File Manager → <code>htdocs/config/database.php</code> → remplacez <code>DB_PASS</code></li>';
echo '<li>Rechargez cette page</li>';
echo '</ol>';

echo '<h3>Contenu exact pour config/database.php :</h3>';
echo '<pre>&lt;?php
define(\'DB_HOST\', \'sql205.infinityfree.com\');
define(\'DB_PORT\', \'3306\');
define(\'DB_NAME\', \'if0_42871659_genies\');
define(\'DB_USER\', \'if0_42871659\');
define(\'DB_PASS\', \'JojYwJiexiP1TAd\');
define(\'DB_CHARSET\', \'utf8mb4\');
define(\'BASE_URL\', \'https://genies.free.je\');
define(\'DEBUG_MODE\', false);</pre>';

echo '<hr><p><small>Supprimez test_db.php après résolution.</small></p>';
echo '</body></html>';
