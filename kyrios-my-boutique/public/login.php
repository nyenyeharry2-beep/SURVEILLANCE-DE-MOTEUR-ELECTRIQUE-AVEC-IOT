<?php
require_once __DIR__ . '/bootstrap.php';

if ($auth->user()) {
    header('Location: /index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->loginWithCredentials($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        header('Location: /index.php');
        exit;
    }
    $error = $result['error'];
}

$config = appConfig();
$googleAuthUrl = '';
if (!empty($config['google']['client_id'])) {
    $params = http_build_query([
        'client_id' => $config['google']['client_id'],
        'redirect_uri' => $config['google']['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
    ]);
    $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · Kyrios My Boutique</title>
    <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="/assets/img/logo.svg" alt="Kyrios My Boutique">
            <h1>Kyrios My Boutique</h1>
            <p>Votre marketplace sociale — achetez, vendez, connectez-vous</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="votre@email.com">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <?php if ($googleAuthUrl): ?>
        <div class="divider">ou</div>
        <a href="<?= e($googleAuthUrl) ?>" class="btn btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Continuer avec Google
        </a>
        <?php endif; ?>

        <p style="text-align:center;margin-top:20px;font-size:0.9rem;color:var(--text-muted);">
            Pas encore de compte ? <a href="/register.php">Créer un compte</a>
        </p>
    </div>
</div>
</body>
</html>
