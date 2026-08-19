<?php
require_once __DIR__ . '/common.php';

header('Content-Type: text/html; charset=utf-8');

if (DB_PASS === 'COLLEZ_MOT_DE_PASSE_MYSQL' || DB_PASS === '') {
  echo '<!DOCTYPE html><html lang="fr"><body style="font-family:sans-serif;padding:40px">';
  echo '<h1>Mot de passe MySQL manquant</h1>';
  echo '<p>Dans le File Manager, ouvrez <code>config.php</code> et collez le mot de passe MySQL.</p>';
  echo '</body></html>';
  exit;
}

try {
  $pdo = db();
  ensure_schema($pdo);
  echo '<!DOCTYPE html><html lang="fr"><body style="font-family:sans-serif;padding:40px">';
  echo '<h1>Lumen — installation OK</h1>';
  echo '<p>Les tables MySQL sont prêtes. Le compte tableau de bord a été créé.</p>';
  echo '<p>E-mail : <code>' . htmlspecialchars(APP_EMAIL, ENT_QUOTES, 'UTF-8') . '</code></p>';
  echo '<p>Mot de passe : <code>' . htmlspecialchars(APP_PASSWORD, ENT_QUOTES, 'UTF-8') . '</code></p>';
  echo '<p><a href="index.html">Ouvrir le tableau de bord</a></p>';
  echo '</body></html>';
} catch (Throwable $e) {
  http_response_code(500);
  echo '<!DOCTYPE html><html lang="fr"><body style="font-family:sans-serif;padding:40px">';
  echo '<h1>Installation impossible</h1>';
  echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
  echo '<p>Vérifiez le mot de passe dans <code>config.php</code>.</p>';
  echo '</body></html>';
}
