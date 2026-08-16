<?php
require_once __DIR__ . '/common.php';

start_session();
$body = read_json();
$email = strtolower(trim((string) ($body['email'] ?? '')));
$password = body_password($body);

if ($email === '') {
  $email = strtolower(APP_EMAIL);
}

$pdo = db();
ensure_schema($pdo);

if ($email === strtolower(APP_EMAIL) && ($password === '' || passwords_equal($password, APP_PASSWORD))) {
  $password = APP_PASSWORD;
  ensure_default_user($pdo);
}

$user = find_user_by_email($pdo, $email);

$valid = $user && verify_stored_password($password, (string) $user['mot_de_passe']);
if (!$valid && $email === strtolower(APP_EMAIL) && passwords_equal($password, APP_PASSWORD)) {
  ensure_default_user($pdo);
  $user = find_user_by_email($pdo, $email);
  $valid = (bool) $user;
}

if (!$user || !$valid) {
  json_error('Identifiants incorrects.', 401);
}

json_ok(['ok' => true, 'user' => sign_in($user)]);
