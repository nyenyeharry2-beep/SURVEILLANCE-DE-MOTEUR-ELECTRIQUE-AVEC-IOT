<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

if (adminIsLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$result = null;
$loginSuccess = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = adminAttemptLogin($_POST['password'] ?? '');
    if ($result['ok']) {
        $loginSuccess = $result;
    }
}

$checks = runAdminDiagnostics();
$config = getAppConfig();

adminLayoutStart('Connexion Admin');
?>
<div class="wrap">
    <div class="card">
        <h1>Administration Web</h1>
        <p class="sub"><?= htmlspecialchars($config['school_name']) ?> — Test connexion avant l'application mobile</p>

        <?php if ($loginSuccess): ?>
            <div class="alert alert-success">
                ✅ <strong>Connexion réussie !</strong> Session active jusqu'à <?= htmlspecialchars($loginSuccess['expires_at']) ?>.
            </div>
            <a class="btn btn-green" href="dashboard.php" style="display:block;text-align:center;margin-bottom:1rem;background:#2e7d32;">Entrer dans l'espace administrateur →</a>
        <?php elseif ($result && !$result['ok']): ?>
            <div class="alert alert-error">
                <strong>Échec — étape : <?= htmlspecialchars($result['step']) ?></strong><br>
                <?= htmlspecialchars($result['message']) ?>
                <?php if (!empty($result['detail'])): ?>
                    <div class="detail">Détail technique : <?= htmlspecialchars($result['detail']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php">
            <label for="password">Mot de passe administrateur</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="SuperGenies2026!">
            <p class="detail" style="margin:.5rem 0 1rem;">Mot de passe par défaut : <code>SuperGenies2026!</code></p>
            <button type="submit" class="btn">Se connecter</button>
            <a class="btn secondary" href="diagnostic.php" style="margin-left:.5rem;">Diagnostic complet</a>
        </form>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">État du serveur (avant connexion)</h1>
        <ul class="checks">
            <?php foreach ($checks as $c): ?>
                <li>
                    <?= $c['ok'] ? '<span class="ok">✓</span>' : '<span class="fail">✗</span>' ?>
                    <?= htmlspecialchars($c['label']) ?>
                    <div class="detail"><?= htmlspecialchars($c['detail']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        $allOk = !in_array(false, array_column($checks, 'ok'), true);
        if ($allOk): ?>
            <div class="alert alert-success" style="margin-top:1rem;">
                Tous les tests serveur sont OK. Si la connexion échoue, le problème est le mot de passe.
            </div>
        <?php else: ?>
            <div class="alert alert-error" style="margin-top:1rem;">
                Corrigez les points en rouge avant de tester l'application Android.
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h1 style="font-size:1.1rem;">Tests API (navigateur)</h1>
        <ul class="checks">
            <li><a href="../api/admin/login.php" target="_blank">api/admin/login.php</a> — doit afficher du JSON</li>
            <li><a href="../api/admin/ping.php" target="_blank">api/admin/ping.php</a> — test API admin</li>
            <li><a href="../api/student.php?matricule=CSLSG-2026-2027-00167" target="_blank">api/student.php</a> — test app Paiements</li>
            <li><a href="../verifier.php" target="_blank">verifier.php</a> — diagnostic global</li>
        </ul>
    </div>
</div>
<?php adminLayoutEnd(); ?>
