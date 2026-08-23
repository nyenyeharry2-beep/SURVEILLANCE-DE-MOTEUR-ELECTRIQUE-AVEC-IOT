<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$convId = (int) ($_GET['conv'] ?? 0);
if (!$convId) {
    echo json_encode(['messages' => []]);
    exit;
}

$messaging = new Kyrios\Messaging($db);
$messages = $messaging->getMessages($convId, (int) $user['id']);

echo json_encode(['messages' => $messages]);
