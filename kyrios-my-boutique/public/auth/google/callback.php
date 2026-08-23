<?php
require_once __DIR__ . '/bootstrap.php';

$config = appConfig();

if (empty($config['google']['client_id']) || empty($config['google']['client_secret'])) {
    header('Location: /login.php?error=google_not_configured');
    exit;
}

$code = $_GET['code'] ?? '';
if (!$code) {
    header('Location: /login.php');
    exit;
}

$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => $config['google']['client_id'],
    'client_secret' => $config['google']['client_secret'],
    'redirect_uri' => $config['google']['redirect_uri'],
    'grant_type' => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokens = json_decode($tokenResponse, true);
if (empty($tokens['access_token'])) {
    header('Location: /login.php?error=google_auth_failed');
    exit;
}

$userInfoUrl = 'https://www.googleapis.com/oauth2/v3/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $tokens['access_token']],
]);
$userResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userResponse, true);
if (empty($googleUser['email'])) {
    header('Location: /login.php?error=google_no_email');
    exit;
}

$isRegister = ($_GET['state'] ?? '') === 'register';

$auth->startSession();

$role = 'client';
if ($isRegister && isset($_SESSION['pending_google_role'])) {
    $role = $_SESSION['pending_google_role'];
}

$result = $auth->loginOrRegisterGoogle($googleUser, $role);

if ($result['success'] && ($result['new'] ?? false) && $isRegister) {
    header('Location: /register-google-role.php');
    exit;
}

header('Location: /index.php');
exit;
