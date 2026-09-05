<?php
/**
 * Assistant de première installation — crée config/config.php depuis le navigateur.
 * Accessible uniquement tant que config.php n'existe pas.
 */
$configPath = __DIR__ . '/config/config.php';
$examplePath = __DIR__ . '/config/config.example.php';

if (file_exists($configPath)) {
    header('Location: login.php');
    exit;
}

$error = '';
$done = false;

$defaults = [
    'db_host' => 'sqlXXX.infinityfree.com',
    'db_name' => 'if0_XXXXXXXX_pharma',
    'db_user' => 'if0_XXXXXXXX',
    'db_pass' => '',
    'app_url' => 'https://mapharmaciepk.xo.je',
    'app_phone' => '+243990525309',
    'taux_usd_cdf' => '2850',
];

function setupEscape(string $value): string
{
    return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
}

function setupTestConnection(string $host, string $name, string $user, string $pass): ?string
{
    $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
    try {
        new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        return null;
    } catch (PDOException $e) {
        return 'Connexion MySQL refusée. Vérifiez DB_HOST, DB_NAME, DB_USER et DB_PASS dans le panneau InfinityFree → MySQL Databases.';
    }
}

function setupWriteConfig(array $values): bool
{
    global $configPath;

    $content = <<<'PHP'
<?php
/**
 * Configuration générée par setup.php — modifiable dans File Manager si besoin.
 */

define('DB_HOST', '%s');
define('DB_NAME', '%s');
define('DB_USER', '%s');
define('DB_PASS', '%s');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Nouvelle Eve');
define('APP_TAGLINE', 'Votre santé, notre priorité');
define('APP_LOGO', 'assets/img/logo.jpg');
define('APP_URL', '%s');
define('APP_ADDRESS', 'Kinshasa, République Démocratique du Congo');
define('APP_PHONE', '%s');
define('TIMEZONE', 'Africa/Kinshasa');

define('TAUX_USD_CDF', %d);
define('DEVISE_DEFAUT', 'CDF');
define('ALERTE_EXPIRATION_MOIS', 5);

date_default_timezone_set(TIMEZONE);

PHP;

    $content = sprintf(
        $content,
        setupEscape($values['db_host']),
        setupEscape($values['db_name']),
        setupEscape($values['db_user']),
        setupEscape($values['db_pass']),
        setupEscape($values['app_url']),
        setupEscape($values['app_phone']),
        (int) $values['taux_usd_cdf']
    );

    if (!is_dir(dirname($configPath))) {
        mkdir(dirname($configPath), 0755, true);
    }

    return file_put_contents($configPath, $content) !== false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [
        'db_host' => trim($_POST['db_host'] ?? ''),
        'db_name' => trim($_POST['db_name'] ?? ''),
        'db_user' => trim($_POST['db_user'] ?? ''),
        'db_pass' => (string) ($_POST['db_pass'] ?? ''),
        'app_url' => rtrim(trim($_POST['app_url'] ?? ''), '/'),
        'app_phone' => trim($_POST['app_phone'] ?? '+243990525309'),
        'taux_usd_cdf' => trim($_POST['taux_usd_cdf'] ?? '2850'),
    ];

    if ($values['db_host'] === '' || $values['db_name'] === '' || $values['db_user'] === '') {
        $error = 'Remplissez au minimum l\'hôte, le nom de la base et l\'utilisateur MySQL.';
    } elseif (!is_numeric($values['taux_usd_cdf']) || (int) $values['taux_usd_cdf'] <= 0) {
        $error = 'Le taux USD → CDF doit être un nombre positif.';
    } elseif ($values['app_url'] === '' || !preg_match('#^https?://#i', $values['app_url'])) {
        $error = 'L\'URL du site doit commencer par http:// ou https://';
    } else {
        $connError = setupTestConnection(
            $values['db_host'],
            $values['db_name'],
            $values['db_user'],
            $values['db_pass']
        );

        if ($connError !== null) {
            $error = $connError;
        } elseif (!setupWriteConfig($values)) {
            $error = 'Impossible d\'écrire config/config.php. Créez-le manuellement : copiez config/config.example.php vers config/config.php dans File Manager.';
        } else {
            $done = true;
        }
    }

    if (!$done) {
        $defaults = $values;
    }
}

$hasExample = file_exists($examplePath);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration — Nouvelle Eve</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card card p-4 mx-3" style="max-width: 560px;">
        <div class="text-center mb-4">
            <img src="assets/img/logo.jpg" alt="Nouvelle Eve" class="login-logo mb-2" width="90" height="90">
            <h1 class="login-title mb-1">Configuration initiale</h1>
            <p class="login-tagline mb-0">Créez <code>config/config.php</code> en une étape</p>
        </div>

        <?php if ($done): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            <strong>Configuration enregistrée.</strong> Le fichier <code>config/config.php</code> a été créé.
        </div>
        <ol class="small text-muted mb-4">
            <li>Si c'est une <strong>nouvelle</strong> installation, importez <code>database/schema_nouvelle_eve.sql</code> dans phpMyAdmin.</li>
            <li>Si <strong>dashboard.php</strong> affiche une erreur 500, ouvrez <a href="reparer.php">reparer.php</a> (connecté admin).</li>
            <li>Si le site existait déjà, connectez-vous en admin puis ouvrez <a href="install_migration.php">install_migration.php</a>.</li>
            <li>Vérifiez le serveur avec <a href="diagnostic.php">diagnostic.php</a>.</li>
        </ol>
        <a href="login.php" class="btn btn-primary w-100">
            <i class="bi bi-box-arrow-in-right me-1"></i> Aller à la connexion
        </a>
        <?php else: ?>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="alert alert-info small">
            <strong>InfinityFree :</strong> panneau → <em>MySQL Databases</em> pour copier l'hôte, la base, l'utilisateur et le mot de passe.
            <?php if (!$hasExample): ?>
            <br><span class="text-danger">Fichier config.example.php introuvable — ré-uploadez le package complet.</span>
            <?php endif; ?>
        </div>

        <form method="post" autocomplete="off">
            <h2 class="h6 text-muted mb-3">Base de données MySQL</h2>
            <div class="mb-3">
                <label class="form-label">Hôte MySQL (DB_HOST)</label>
                <input type="text" name="db_host" class="form-control" required
                       value="<?= htmlspecialchars($defaults['db_host'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="sql123.infinityfree.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Nom de la base (DB_NAME)</label>
                <input type="text" name="db_name" class="form-control" required
                       value="<?= htmlspecialchars($defaults['db_name'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="if0_12345678_pharma">
            </div>
            <div class="mb-3">
                <label class="form-label">Utilisateur (DB_USER)</label>
                <input type="text" name="db_user" class="form-control" required
                       value="<?= htmlspecialchars($defaults['db_user'], ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="if0_12345678">
            </div>
            <div class="mb-4">
                <label class="form-label">Mot de passe (DB_PASS)</label>
                <input type="password" name="db_pass" class="form-control"
                       value="<?= htmlspecialchars($defaults['db_pass'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <h2 class="h6 text-muted mb-3">Site</h2>
            <div class="mb-3">
                <label class="form-label">URL du site (APP_URL)</label>
                <input type="url" name="app_url" class="form-control" required
                       value="<?= htmlspecialchars($defaults['app_url'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Téléphone (APP_PHONE)</label>
                <input type="text" name="app_phone" class="form-control"
                       value="<?= htmlspecialchars($defaults['app_phone'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="mb-4">
                <label class="form-label">Taux 1 USD = X CDF</label>
                <input type="number" name="taux_usd_cdf" class="form-control" min="1" required
                       value="<?= htmlspecialchars($defaults['taux_usd_cdf'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-gear me-1"></i> Créer config.php et tester la connexion
            </button>
        </form>

        <p class="text-muted text-center small mt-3 mb-0">
            Alternative manuelle : File Manager → <code>htdocs/config/</code> → copier
            <code>config.example.php</code> en <code>config.php</code> puis éditer.
        </p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
