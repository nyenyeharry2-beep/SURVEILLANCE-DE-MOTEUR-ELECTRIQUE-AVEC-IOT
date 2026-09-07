<?php
/**
 * Fichier de diagnostic — ouvrir dans le navigateur :
 * http://VOTRE-DOMAINE/verifier.php
 * Supprimez ce fichier après vérification.
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Diagnostic Super Genies</title>
    <style>
        body { font-family: sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        .ok { color: #2e7d32; font-weight: bold; }
        .fail { color: #c62828; font-weight: bold; }
        li { margin: 0.5rem 0; }
    </style>
</head>
<body>
    <h1>Diagnostic Super Genies</h1>
    <ul>
        <li>PHP : <span class="ok"><?= htmlspecialchars(PHP_VERSION) ?></span></li>
        <li>Date serveur : <?= date('Y-m-d H:i:s') ?></li>
        <li>Dossier : <?= htmlspecialchars(__DIR__) ?></li>
        <?php
        $files = ['index.php', 'config/database.php', 'api/student.php', '.htaccess'];
        foreach ($files as $f) {
            $ok = file_exists(__DIR__ . '/' . $f);
            echo '<li>Fichier ' . htmlspecialchars($f) . ' : ';
            echo $ok ? '<span class="ok">OK</span>' : '<span class="fail">MANQUANT</span>';
            echo '</li>';
        }

        $dbOk = false;
        $dbMsg = '';
        try {
            if (file_exists(__DIR__ . '/config/database.php')) {
                $cfg = require __DIR__ . '/config/database.php';
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $cfg['host'], $cfg['port'], $cfg['dbname']);
                $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $count = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                $dbOk = true;
                $dbMsg = "Connexion OK — $count élève(s) en base";
            } else {
                $dbMsg = 'config/database.php introuvable';
            }
        } catch (Throwable $e) {
            $dbMsg = 'Échec connexion MySQL (vérifiez phpMyAdmin et schema.sql)';
        }
        ?>
        <li>Base MySQL : <?= $dbOk ? '<span class="ok">' . htmlspecialchars($dbMsg) . '</span>' : '<span class="fail">' . htmlspecialchars($dbMsg) . '</span>' ?></li>
    </ul>
    <?php if ($dbOk): ?>
        <p class="ok">Tout est prêt. Les applications Android peuvent se connecter.</p>
    <?php else: ?>
        <p class="fail">Corrigez les points en rouge, puis réessayez.</p>
    <?php endif; ?>
    <p><small>Test API : <a href="api/student.php?matricule=CSLSG-2026-2027-00167">api/student.php</a></small></p>
</body>
</html>
