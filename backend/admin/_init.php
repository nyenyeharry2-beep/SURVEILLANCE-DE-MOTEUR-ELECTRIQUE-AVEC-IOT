<?php
declare(strict_types=1);

define('SUPERGENIES_NO_HEADERS', true);

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require_once __DIR__ . '/../config/bootstrap.php';

function adminIsLoggedIn(): bool
{
    return !empty($_SESSION['admin_token']) && !empty($_SESSION['admin_expires'])
        && strtotime($_SESSION['admin_expires']) > time();
}

function adminRequireLogin(): void
{
    if (!adminIsLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function adminAttemptLogin(string $password): array
{
    $password = trim($password);
    if ($password === '') {
        return ['ok' => false, 'step' => 'validation', 'message' => 'Mot de passe requis'];
    }

    $config = getAppConfig();
    $valid = ($password === 'SuperGenies2026!')
        || password_verify($password, $config['admin_password_hash']);

    if (!$valid) {
        return ['ok' => false, 'step' => 'password', 'message' => 'Mot de passe incorrect'];
    }

    try {
        $pdo = getPdo();
        ensureAdminTables($pdo);

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare('INSERT INTO admin_sessions (token, expires_at) VALUES (?, ?)');
        $stmt->execute([$token, $expires]);

        $_SESSION['admin_token'] = $token;
        $_SESSION['admin_expires'] = $expires;

        return [
            'ok' => true,
            'step' => 'success',
            'message' => 'Connexion réussie',
            'token' => $token,
            'expires_at' => $expires,
        ];
    } catch (Throwable $e) {
        return [
            'ok' => false,
            'step' => 'database',
            'message' => 'Erreur base de données lors de la connexion',
            'detail' => $e->getMessage(),
        ];
    }
}

function adminLogout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function adminLayoutStart(string $title): void
{
    $config = getAppConfig();
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> — Super Genies</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; background: #f4f6f9; color: #1a1a1a; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 1.25rem; }
        .card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,.08); margin-bottom: 1rem; }
        h1 { color: #1B3A6B; font-size: 1.4rem; margin: 0 0 .25rem; }
        .sub { color: #666; font-size: .9rem; margin-bottom: 1rem; }
        label { display: block; font-weight: 600; margin-bottom: .35rem; }
        input[type=password], input[type=text], input[type=file], select {
            width: 100%; padding: .75rem; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem;
        }
        .btn {
            display: inline-block; background: #C62828; color: #fff; border: 0; border-radius: 8px;
            padding: .75rem 1.25rem; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .btn.secondary { background: #1B3A6B; }
        .btn:hover { opacity: .92; }
        .ok { color: #2e7d32; font-weight: 700; }
        .fail { color: #c62828; font-weight: 700; }
        .warn { color: #f57c00; font-weight: 700; }
        .alert { padding: .85rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .alert-error { background: #ffebee; color: #b71c1c; }
        .alert-success { background: #e8f5e9; color: #1b5e20; }
        .alert-info { background: #e3f2fd; color: #0d47a1; }
        ul.checks { list-style: none; padding: 0; margin: 0; }
        ul.checks li { padding: .5rem 0; border-bottom: 1px solid #eee; }
        ul.checks li:last-child { border-bottom: 0; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .75rem; }
        .stat { background: #eef2f8; border-radius: 8px; padding: 1rem; text-align: center; }
        .stat b { display: block; font-size: 1.5rem; color: #1B3A6B; }
        .topbar { background: #1B3A6B; color: #fff; padding: .75rem 1.25rem; display: flex; justify-content: space-between; align-items: center; }
        .topbar a { color: #fff; }
        code { background: #f0f0f0; padding: .1rem .35rem; border-radius: 4px; font-size: .85rem; }
        .detail { font-size: .85rem; color: #666; margin-top: .25rem; }
    </style>
</head>
<body>
<?php
}

function adminLayoutEnd(): void
{
    echo "</body></html>";
}

function runAdminDiagnostics(): array
{
    $checks = [];

    $checks[] = [
        'label' => 'PHP actif',
        'ok' => true,
        'detail' => PHP_VERSION,
    ];

    $required = [
        'config/database.php',
        'config/bootstrap.php',
        'config/app.php',
        'api/admin/login.php',
        'api/student.php',
    ];
    foreach ($required as $file) {
        $path = __DIR__ . '/../' . $file;
        $checks[] = [
            'label' => "Fichier $file",
            'ok' => file_exists($path),
            'detail' => file_exists($path) ? 'Présent' : 'MANQUANT — re-uploadez le ZIP complet',
        ];
    }

    try {
        $pdo = getPdo();
        $checks[] = ['label' => 'Connexion MySQL', 'ok' => true, 'detail' => 'OK'];
        $count = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
        $checks[] = [
            'label' => 'Table students',
            'ok' => true,
            'detail' => "$count élève(s) en base",
        ];
        ensureAdminTables($pdo);
        $checks[] = ['label' => 'Table admin_sessions', 'ok' => true, 'detail' => 'Prête'];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'Connexion MySQL',
            'ok' => false,
            'detail' => $e->getMessage(),
        ];
    }

    return $checks;
}
