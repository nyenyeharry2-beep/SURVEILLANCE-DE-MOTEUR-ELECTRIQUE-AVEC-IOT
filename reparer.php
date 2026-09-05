<?php
/**
 * Réparation base de données — ouvrez une fois après setup.php si dashboard.php affiche erreur 500.
 * Supprimez ce fichier après réparation.
 */
require_once __DIR__ . '/includes/auth.php';

$configPath = __DIR__ . '/config/config.php';
if (!file_exists($configPath)) {
    header('Location: setup.php');
    exit;
}

$loggedIn = !empty($_SESSION['user_id']);
$results = [];
$error = '';

function reparerCheck(string $label, bool $ok, string $detail = ''): void
{
    global $results;
    $results[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loggedIn) {
    try {
        $db = getDB();
        require_once __DIR__ . '/includes/schema_bootstrap.php';
        ensureAllSchemas($db);

        reparerCheck('Migrations ventes / achats / unités', true);
        reparerCheck('Colonne ventes.annulee', dbColumnExists($db, 'ventes', 'annulee'));
        reparerCheck('Colonne medicaments.type_unite', dbColumnExists($db, 'medicaments', 'type_unite'));
        reparerCheck('Table medicaments', true, (string) $db->query('SELECT COUNT(*) FROM medicaments')->fetchColumn() . ' produits');
    } catch (Throwable $e) {
        $error = $e->getMessage();
        reparerCheck('Réparation', false, $error);
    }
} elseif (file_exists($configPath)) {
    try {
        require_once __DIR__ . '/includes/db.php';
        require_once __DIR__ . '/includes/schema_util.php';
        $db = getDB();
        reparerCheck('config/config.php', true);
        reparerCheck('Connexion MySQL', true);
        reparerCheck('Table utilisateurs', true);
        reparerCheck('Colonne ventes.annulee', dbColumnExists($db, 'ventes', 'annulee'), dbColumnExists($db, 'ventes', 'annulee') ? '' : 'Manquante — cliquez Réparer');
        reparerCheck('Table medicaments', (bool) @$db->query('SELECT 1 FROM medicaments LIMIT 1'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
        reparerCheck('Diagnostic', false, $error);
    }
}

$pageTitle = 'Réparation base';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> — Nouvelle Eve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card card p-4 mx-3" style="max-width: 560px;">
        <h1 class="h4 mb-3">Réparation après configuration</h1>
        <p class="text-muted small">Utilisez cette page si <strong>setup.php</strong> a réussi mais <strong>dashboard.php</strong> affiche une erreur 500.</p>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($results): ?>
        <ul class="list-group mb-3">
            <?php foreach ($results as $r): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($r['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="badge <?= $r['ok'] ? 'bg-success' : 'bg-danger' ?>">
                    <?= $r['ok'] ? 'OK' : 'Erreur' ?>
                </span>
            </li>
            <?php if (!$r['ok'] && $r['detail'] !== ''): ?>
            <li class="list-group-item small text-muted"><?= htmlspecialchars($r['detail'], ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (!$loggedIn): ?>
        <div class="alert alert-warning small">
            Connectez-vous d'abord, puis revenez ici.
        </div>
        <a href="login.php" class="btn btn-primary w-100">Connexion</a>
        <?php else: ?>
        <form method="post" class="mb-3">
            <button type="submit" class="btn btn-primary w-100">Appliquer les migrations / Réparer</button>
        </form>
        <a href="dashboard.php" class="btn btn-outline-secondary w-100">Retour tableau de bord</a>
        <?php endif; ?>

        <p class="text-muted small mt-3 mb-0">
            Nouvelle installation : importez aussi <code>database/schema_nouvelle_eve.sql</code> dans phpMyAdmin si les tables n'existent pas.
        </p>
    </div>
</div>
</body>
</html>
