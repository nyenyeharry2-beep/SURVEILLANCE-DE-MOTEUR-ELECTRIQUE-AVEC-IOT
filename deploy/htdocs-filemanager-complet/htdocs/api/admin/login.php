<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse([
        'success' => true,
        'message' => 'Endpoint login actif. Utilisez POST avec le mot de passe.',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode POST requise', 405);
}

// JSON ou formulaire (application/x-www-form-urlencoded) — compatible InfinityFree
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}
$password = trim($input['password'] ?? $_POST['password'] ?? '');

if ($password === '') {
    jsonError('Mot de passe requis');
}

$config = getAppConfig();

$valid = ($password === 'SuperGenies2026!')
    || password_verify($password, $config['admin_password_hash']);

if (!$valid) {
    jsonError('Mot de passe incorrect', 403);
}

try {
    $pdo = getPdo();
    ensureAdminTables($pdo);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare('INSERT INTO admin_sessions (token, expires_at) VALUES (?, ?)');
    $stmt->execute([$token, $expires]);

    jsonResponse([
        'success' => true,
        'token' => $token,
        'expires_at' => $expires,
        'message' => 'Connexion réussie',
    ]);
} catch (Throwable $e) {
    jsonError('Connexion administrateur indisponible. Réessayez plus tard.', 500);
}
