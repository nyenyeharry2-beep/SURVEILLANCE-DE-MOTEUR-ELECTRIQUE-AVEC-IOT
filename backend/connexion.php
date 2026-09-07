<?php
declare(strict_types=1);

/**
 * Page publique de connexion admin — lien direct :
 * http://supergenies2026.site.je/connexion.php
 */
define('SUPERGENIES_NO_HEADERS', true);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/config/bootstrap.php';

$siteUrl = 'http://supergenies2026.site.je';
$config = getAppConfig();
$error = null;
$success = null;

if (!empty($_SESSION['admin_token']) && !empty($_SESSION['admin_expires'])
    && strtotime($_SESSION['admin_expires']) > time()) {
    $success = 'Vous êtes déjà connecté.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');

    if ($password === '') {
        $error = 'Entrez le mot de passe.';
    } else {
        $valid = ($password === 'SuperGenies2026!')
            || password_verify($password, $config['admin_password_hash']);

        if (!$valid) {
            $error = 'Mot de passe incorrect. Utilisez : SuperGenies2026!';
        } else {
            try {
                $pdo = getPdo();
                ensureAdminTables($pdo);
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
                $stmt = $pdo->prepare('INSERT INTO admin_sessions (token, expires_at) VALUES (?, ?)');
                $stmt->execute([$token, $expires]);
                $_SESSION['admin_token'] = $token;
                $_SESSION['admin_expires'] = $expires;
                $success = 'Connexion réussie ! Vous pouvez entrer dans l\'espace administrateur.';
            } catch (Throwable $e) {
                $error = 'Erreur base de données : ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion Admin — Super Genies</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, sans-serif; margin: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #1B3A6B 0%, #0d2137 100%);
            padding: 1rem;
        }
        .box {
            background: #fff; border-radius: 16px; padding: 2rem;
            width: 100%; max-width: 420px; box-shadow: 0 8px 32px rgba(0,0,0,.25);
        }
        h1 { color: #1B3A6B; margin: 0 0 .25rem; font-size: 1.5rem; text-align: center; }
        .school { text-align: center; color: #666; margin-bottom: 1.5rem; font-size: .9rem; }
        label { display: block; font-weight: 600; margin-bottom: .5rem; color: #333; }
        input[type=password] {
            width: 100%; padding: .85rem; border: 2px solid #ddd; border-radius: 10px;
            font-size: 1.1rem; margin-bottom: 1rem;
        }
        input[type=password]:focus { border-color: #1B3A6B; outline: none; }
        .btn {
            width: 100%; background: #C62828; color: #fff; border: 0; border-radius: 10px;
            padding: .9rem; font-size: 1.1rem; font-weight: 700; cursor: pointer;
        }
        .btn:hover { background: #b71c1c; }
        .btn-green { background: #2e7d32; margin-top: .75rem; display: block; text-align: center; text-decoration: none; }
        .btn-green:hover { background: #1b5e20; }
        .msg-ok {
            background: #e8f5e9; color: #1b5e20; padding: 1rem; border-radius: 10px;
            margin-bottom: 1rem; font-weight: 600; text-align: center; border: 2px solid #2e7d32;
        }
        .msg-err {
            background: #ffebee; color: #b71c1c; padding: 1rem; border-radius: 10px;
            margin-bottom: 1rem; font-weight: 600; text-align: center;
        }
        .hint { font-size: .85rem; color: #888; text-align: center; margin-top: 1rem; }
        .hint code { background: #f5f5f5; padding: .15rem .4rem; border-radius: 4px; }
        .link { display: block; text-align: center; margin-top: 1rem; color: #1B3A6B; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔐 Espace Admin</h1>
        <p class="school"><?= htmlspecialchars($config['school_name']) ?></p>

        <?php if ($success): ?>
            <div class="msg-ok">✅ <?= htmlspecialchars($success) ?></div>
            <a class="btn btn-green" href="<?= $siteUrl ?>/admin/dashboard.php">Entrer dans l'espace administrateur →</a>
            <p class="hint">Lien direct : <br><a href="<?= $siteUrl ?>/admin/dashboard.php"><?= $siteUrl ?>/admin/dashboard.php</a></p>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="msg-err">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= $siteUrl ?>/connexion.php">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required
                       placeholder="Tapez votre mot de passe ici" autofocus>
                <button type="submit" class="btn">SE CONNECTER</button>
            </form>
            <p class="hint">Mot de passe : <code>SuperGenies2026!</code></p>
        <?php endif; ?>

        <a class="link" href="<?= $siteUrl ?>/admin/diagnostic.php">Diagnostic serveur</a>
    </div>
</body>
</html>
