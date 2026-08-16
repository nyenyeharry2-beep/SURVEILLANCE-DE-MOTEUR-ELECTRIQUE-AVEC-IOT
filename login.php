<?php
require_once __DIR__ . '/common.php';

start_session();
$body = read_json();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = body_password($body);

if ($email === '') {
  $email = strtolower(APP_EMAIL);
}
if ($password === '') {
  $password = APP_PASSWORD;
}

$pdo = db();
ensure_schema($pdo);

$stmt = $pdo->prepare('SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['mot_de_passe'])) {
  json_error('Identifiants incorrects.', 401);
}

json_ok(['ok' => true, 'user' => sign_in($user)]);
