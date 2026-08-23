<?php
/**
 * Diagnostic Kyrios My Boutique
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Diagnostic Kyrios</title>
<style>
body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px}
.ok{color:green}.err{color:red}
table{width:100%;border-collapse:collapse;margin:16px 0}
td,th{border:1px solid #ddd;padding:10px;text-align:left}
.btn{display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;margin-top:16px}
</style>
</head>
<body>
<h1>🔧 Diagnostic Kyrios My Boutique</h1>
<p>PHP version: <strong><?= phpversion() ?></strong></p>
<table>
<tr><th>Test</th><th>Résultat</th></tr>
<?php
$envOk = file_exists(__DIR__ . '/.env');
echo '<tr><td>Fichier .env</td><td class="' . ($envOk ? 'ok' : 'err') . '">' . ($envOk ? 'OK' : 'MANQUANT - uploadez .env') . '</td></tr>';

echo '<tr><td>index.php</td><td class="' . (file_exists(__DIR__.'/index.php') ? 'ok' : 'err') . '">' . (file_exists(__DIR__.'/index.php') ? 'OK' : 'MANQUANT') . '</td></tr>';

echo '<tr><td>login.php</td><td class="' . (file_exists(__DIR__.'/login.php') ? 'ok' : 'err') . '">' . (file_exists(__DIR__.'/login.php') ? 'OK' : 'MANQUANT') . '</td></tr>';

try {
    $config = require __DIR__ . '/config/config.php';
    echo '<tr><td>Configuration</td><td class="ok">OK</td></tr>';
    echo '<tr><td>Base de données</td><td>' . htmlspecialchars($config['db']['name']) . '</td></tr>';

    require_once __DIR__ . '/src/Database.php';
    $db = Kyrios\Database::getInstance($config['db']);
    echo '<tr><td>Connexion MySQL</td><td class="ok">OK</td></tr>';
    $n = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo '<tr><td>Comptes utilisateurs</td><td class="ok">' . $n . ' compte(s)</td></tr>';
} catch (Exception $e) {
    echo '<tr><td>Erreur</td><td class="err">' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>
</table>
<a href="login.php" class="btn">→ Page de connexion</a>
</body>
</html>
