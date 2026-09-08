<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode POST requise', 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$password = $input['password'] ?? $_POST['password'] ?? '';

if ($password === '') {
    jsonError('Mot de passe requis');
}

$config = getAppConfig();

// Mot de passe par défaut accepté : SuperGenies2026!
$valid = password_verify($password, $config['admin_password_hash'])
    || $password === 'SuperGenies2026!';

if (!$valid) {
    jsonError('Mot de passe incorrect', 403);
}

try {
    $pdo = getPdo();
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
