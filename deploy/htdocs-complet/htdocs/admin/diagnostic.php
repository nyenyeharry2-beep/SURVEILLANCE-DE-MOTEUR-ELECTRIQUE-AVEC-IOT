<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$checks = runAdminDiagnostics();

// Test POST login avec mot de passe (sans garder la session)
$testPassword = $_GET['test_password'] ?? '';
$loginTestResult = null;
if ($testPassword !== '') {
    $loginTestResult = adminAttemptLogin($testPassword);
    adminLogout();
    session_start();
}

adminLayoutStart('Diagnostic Admin');
?>
<div class="wrap">
    <div class="card">
        <h1>Diagnostic complet</h1>
        <p class="sub">Utilisez cette page pour identifier exactement où ça bloque.</p>
        <a class="btn secondary" href="index.php">← Retour connexion</a>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">1. Fichiers et base de données</h1>
        <ul class="checks">
            <?php foreach ($checks as $c): ?>
                <li>
                    <?= $c['ok'] ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ?>
                    <?= htmlspecialchars($c['label']) ?>
                    <div class="detail"><?= htmlspecialchars($c['detail']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">2. API login.php (GET — ouvrir dans le navigateur)</h1>
        <p>Ouvrez ce lien : <a href="../api/admin/login.php" target="_blank"><strong>api/admin/login.php</strong></a></p>
        <p class="detail">Vous devez voir : <code>{"success": true, "message": "Endpoint login actif..."}</code></p>
        <p>Si vous voyez une erreur 404, 502 ou une page HTML → le fichier n'est pas bien uploadé ou l'hébergement est down.</p>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">3. Test connexion web (POST mot de passe)</h1>
        <form method="get" action="">
            <label for="test_password">Mot de passe à tester</label>
            <input type="password" id="test_password" name="test_password" placeholder="SuperGenies2026!">
            <button type="submit" class="btn" style="margin-top:1rem;">Tester la connexion</button>
        </form>
        <?php if ($loginTestResult !== null): ?>
            <?php if ($loginTestResult['ok']): ?>
                <div class="alert alert-success">
                    Connexion OK — le serveur admin fonctionne parfaitement en web.
                    <div class="detail">Expire : <?= htmlspecialchars($loginTestResult['expires_at']) ?></div>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    Échec à l'étape <strong><?= htmlspecialchars($loginTestResult['step']) ?></strong> :
                    <?= htmlspecialchars($loginTestResult['message']) ?>
                    <?php if (!empty($loginTestResult['detail'])): ?>
                        <div class="detail"><?= htmlspecialchars($loginTestResult['detail']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">4. Interprétation</h1>
        <ul>
            <li><span class="ok">Tout vert + connexion OK</span> → Le problème vient de l'application Android, pas du serveur.</li>
            <li><span class="fail">MySQL en rouge</span> → Importez <code>schema.sql</code> dans phpMyAdmin.</li>
            <li><span class="fail">Fichier manquant</span> → Re-uploadez <code>supergenies-htdocs-complet.zip</code>.</li>
            <li><span class="fail">502 / page blanche</span> → Hébergement InfinityFree temporairement down ou domaine inactif.</li>
        </ul>
    </div>
</div>
<?php adminLayoutEnd(); ?>
