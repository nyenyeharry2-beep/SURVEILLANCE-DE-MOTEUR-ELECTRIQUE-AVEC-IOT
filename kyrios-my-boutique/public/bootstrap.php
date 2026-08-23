<?php

$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Feed.php';
require_once __DIR__ . '/src/Messaging.php';
require_once __DIR__ . '/src/Product.php';
require_once __DIR__ . '/src/Upload.php';
require_once __DIR__ . '/src/Payment.php';

try {
    $db = Kyrios\Database::getInstance($config['db']);
} catch (Exception $e) {
    if (!empty($config['debug'])) {
        die('Erreur DB: ' . htmlspecialchars($e->getMessage()));
    }
    die('Service temporairement indisponible. Vérifiez la base de données.');
}

$auth = new Kyrios\Auth($db);

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function avatarUrl($url, $name)
{
    if ($url) {
        return $url;
    }
    $initial = strtoupper(substr($name, 0, 1));
    return 'data:image/svg+xml,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect fill="#6366f1" width="40" height="40" rx="20"/><text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="18" font-family="sans-serif">' . $initial . '</text></svg>'
    );
}

function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'À l\'instant';
    if ($diff < 3600) return floor($diff / 60) . ' min';
    if ($diff < 86400) return floor($diff / 3600) . ' h';
    if ($diff < 604800) return floor($diff / 86400) . ' j';
    return date('d/m/Y', $time);
}

function roleBadge($role)
{
    if ($role === 'vendeur') {
        return '<span class="badge badge-seller">Vendeur</span>';
    }
    if ($role === 'livreur') {
        return '<span class="badge badge-delivery">Livreur</span>';
    }
    return '<span class="badge badge-client">Client</span>';
}

function productImageHtml($url, $class = 'product-card-image')
{
    if ($url) {
        return '<div class="' . e($class) . '"><img src="' . e($url) . '" alt="" style="width:100%;height:100%;object-fit:cover;"></div>';
    }
    return '<div class="' . e($class) . '">🛍️</div>';
}

function googleAuthUrl($config, $state = '')
{
    if (empty($config['google']['client_id'])) {
        return '';
    }
    $params = [
        'client_id' => $config['google']['client_id'],
        'redirect_uri' => $config['google']['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];
    if ($state) {
        $params['state'] = $state;
    }
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function appConfig()
{
    global $config;
    return $config;
}
