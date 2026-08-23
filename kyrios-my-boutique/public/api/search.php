<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$messaging = new Kyrios\Messaging($db);
$results = $messaging->searchUsers($query, (int) $user['id']);

echo json_encode($results);
