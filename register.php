<?php
require_once __DIR__ . '/common.php';

start_session();
$body = read_json();
$nom = trim((string) ($body['name'] ?? $body['nom'] ?? ''));
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = body_password($body);

if ($nom === '' || $email === '' || strlen($password) < 6) {
  json_error('Nom, e-mail et mot de passe (6 caractères min.) requis.');
}

$pdo = db();
ensure_schema($pdo);

try {
  $stmt = $pdo->prepare(
    'INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)'
  );
  $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
  if ((int) $e->getCode() === 23000) {
    $existing = $pdo->prepare('SELECT id, nom, email, mot_de_passe FROM utilisateurs WHERE email = ? LIMIT 1');
    $existing->execute([$email]);
    $user = $existing->fetch();
    if ($user && password_verify($password, $user['mot_de_passe'])) {
      json_ok(['ok' => true, 'user' => sign_in($user)]);
    }
    json_error('Cet e-mail est déjà utilisé. Connectez-vous avec le même mot de passe.');
  }
  json_error('Inscription impossible.', 500);
}

json_ok([
  'ok' => true,
  'user' => sign_in([
    'id' => (int) $pdo->lastInsertId(),
    'nom' => $nom,
    'email' => $email,
  ]),
]);
