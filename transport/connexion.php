<?php
/**
 * Test connexion - https://genies.free.je/connexion.php
 * Supprimez ce fichier après succès
 */
header('Content-Type: text/html; charset=utf-8');

$tests = [
    ['JojYwJiexiP1TAd', 'Panel actuel'],
    ['JoJyWjiexiP1TAd', 'Ancienne variante'],
];

$host = 'sql205.infinityfree.com';
$user = 'if0_42871659';
$db   = 'if0_42871659_genies';

echo '<h1>Test connexion MySQL</h1>';
echo '<p>Host: ' . htmlspecialchars($host) . ' | User: ' . htmlspecialchars($user) . ' | Base: ' . htmlspecialchars($db) . '</p>';

$ok = false;
foreach ($tests as [$pass, $label]) {
    echo '<h3>' . htmlspecialchars($label) . ' : ' . htmlspecialchars(substr($pass, 0, 3)) . '...' . htmlspecialchars(substr($pass, -4)) . '</h3>';
    try {
        $pdo = new PDO("mysql:host=$host;port=3306;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo '<p style="color:green;font-size:18px;"><strong>✅ CONNEXION OK avec ce mot de passe !</strong></p>';
        echo '<p>Tables: ' . count($tables) . '</p>';
        echo '<p>Mettez dans config/database.php :</p>';
        echo '<pre>define(\'DB_PASS\', \'' . htmlspecialchars($pass) . '\');</pre>';
        echo '<p><a href="admin/login.php">→ Admin</a> | <a href="inscription.php">→ Inscription</a></p>';
        $ok = true;
        break;
    } catch (PDOException $e) {
        echo '<p style="color:red;">❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

if (!$ok) {
    echo '<hr><h2 style="color:red;">Aucun mot de passe ne fonctionne</h2>';
    echo '<ol>';
    echo '<li>InfinityFree → <strong>MySQL Databases</strong></li>';
    echo '<li>Cliquez <strong>Change Password</strong></li>';
    echo '<li>Tapez manuellement : <strong>GeniesBus2026</strong></li>';
    echo '<li>Attendez 2 minutes</li>';
    echo '<li>Modifiez config/database.php : <code>define(\'DB_PASS\', \'GeniesBus2026\');</code></li>';
    echo '<li>Rechargez cette page</li>';
    echo '</ol>';
}
