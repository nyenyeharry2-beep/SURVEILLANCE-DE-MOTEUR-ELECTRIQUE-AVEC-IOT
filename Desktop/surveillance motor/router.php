<?php
/**
 * Routeur pour `php -S host:port router.php` et hôtes sans rewrite.
 */
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = is_string($uri) ? $uri : '/';
$uri = '/' . ltrim($uri, '/');
$normalized = rtrim($uri, '/');
if ($normalized === '') {
  $normalized = '/';
}

$aliases = [
  '/mesure' => 'mesure.php',
  '/commande' => 'commande.php',
  '/etat' => 'etat.php',
  '/ping' => 'ping.php',
  '/login' => 'login.php',
  '/logout' => 'logout.php',
  '/register' => 'register.php',
  '/me' => 'me.php',
];

if (isset($aliases[$normalized])) {
  require __DIR__ . '/' . $aliases[$normalized];
  return true;
}

if ($normalized !== '/' && is_file(__DIR__ . $normalized . '.php')) {
  require __DIR__ . $normalized . '.php';
  return true;
}

$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
  $ext = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
  if ($ext === 'php' || $ext === '') {
    require $file;
    return true;
  }
  return false;
}

return false;
