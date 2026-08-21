<?php
/**
 * install.php - Installation automatique des tables
 * Ouvrir une seule fois : http://surveillancemoteurharry.ct.ws/install.php
 */
require_once __DIR__ . '/config.php';

$steps = [];
$ok = true;

try {
    $pdo = getDbConnection();
    $steps[] = ['ok' => true, 'msg' => 'Connexion MySQL reussie (' . DB_HOST . ')'];

    ensureDatabaseSchema();
    $steps[] = ['ok' => true, 'msg' => 'Tables creees ou deja presentes'];

    $tables = ['moteur_surveillance', 'commandes', 'etat_relais'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            $steps[] = ['ok' => true, 'msg' => "Table '$table' OK"];
        } else {
            $steps[] = ['ok' => false, 'msg' => "Table '$table' manquante"];
            $ok = false;
        }
    }
} catch (PDOException $e) {
    $ok = false;
    $steps[] = ['ok' => false, 'msg' => getDbErrorMessage($e)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Installation base de donnees</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f8fafc; }
        .ok { color: #166534; }
        .err { color: #991b1b; }
        li { margin: 8px 0; }
        a { display: inline-block; margin-top: 20px; padding: 10px 16px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Installation base de donnees</h1>
    <ul>
        <?php foreach ($steps as $step): ?>
            <li class="<?= $step['ok'] ? 'ok' : 'err' ?>">
                <?= $step['ok'] ? 'OK' : 'ERREUR' ?> — <?= htmlspecialchars($step['msg']) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($ok): ?>
        <p class="ok"><strong>Installation terminee.</strong> Vous pouvez ouvrir le dashboard.</p>
        <a href="dashboard.php">Ouvrir dashboard.php</a>
    <?php else: ?>
        <p class="err"><strong>Installation echouee.</strong> Verifiez config.php puis reessayez.</p>
    <?php endif; ?>
</body>
</html>
